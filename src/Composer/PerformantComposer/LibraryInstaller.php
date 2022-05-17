<?php

namespace Composer\PerformantComposer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\PerformantComposer\PackageUtils;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\LibraryInstaller as BaseLibraryInstaller;
use Composer\PerformantComposer\Installer\Filesystem\SymlinkFilesystem;
use Composer\PerformantComposer\Installer\Config\SharedPackageInstallerConfig;

class LibraryInstaller extends BaseLibraryInstaller
{
    /**
     * @param IOInterface                  $io
     * @param Composer                     $composer
     * @param SymlinkFilesystem            $filesystem
     * @param PackageDataManagerInterface  $dataManager
     * @param SharedPackageInstallerConfig $config
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        SymlinkFilesystem $filesystem,
        Package\PackageDataManagerInterface $dataManager,
        SharedPackageInstallerConfig $config
    ) {
        $this->filesystem = $filesystem;

        parent::__construct($io, $composer, 'library', $this->filesystem);

        $this->config = $config;
        $this->vendorDir = $this->config->getOriginalVendorDir();
        $this->packageDataManager = $dataManager;
        $this->packageDataManager->setVendorDir($this->vendorDir);

    }

    public function getInstallPath(PackageInterface $package)
    {
        $this->initializeVendorDir();

        $extra = [];
        if ($this->composer->getPluginManager()->getGlobalComposer()) {
            $extra = $this->composer->getPluginManager()->getGlobalComposer()->getConfig()->get('extra');
        }

        $path = PackageUtils::getPackageInstallPath($package, $this->composer, $extra);

        if (!empty($path)) {
            return $path;
        }


        /*
         * In case, the user didn't provide a custom path
         * use the default one, by calling the parent::getInstallPath function
         */
        return parent::getInstallPath($package);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return
            $repo->hasPackage($package)
            && is_readable($this->getInstallPath($package))
            && (!PackageUtils::isPackageExcluded($package, [], $this->composer) && is_link($this->getPackageVendorSymlink($package)))
        ;
    }

    /**
     * @param PackageInterface $package
     *
     * @return string
     */
    protected function getPackageVendorSymlink(PackageInterface $package)
    {
        return $this->config->getSymlinkDir() . DIRECTORY_SEPARATOR . $package->getPrettyName();
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     */

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!$this->filesystem->isReadable($this->getInstallPath($package)) || $this->filesystem->isDirEmpty($this->getInstallPath($package))) {
            $prom = parent::install($repo, $package);
        } elseif (!$repo->hasPackage($package)) {
            $prom = \React\Promise\resolve();
            $binaryInstaller = $this->binaryInstaller;
            $installPath = $this->getInstallPath($package);

            $prom = $prom->then(function () use ($binaryInstaller, $installPath, $package, $repo) {
                $binaryInstaller->installBinaries($package, $installPath);
                if (!$repo->hasPackage($package)) {
                    $repo->addPackage(clone $package);
                }
            });
        }

        $composer = $this->composer;
        $prom = $prom->then(function () use ($package, $composer) {
            if (!PackageUtils::isPackageExcluded($package, [], $composer)) {
                $this->createPackageVendorSymlink($package);
            }
            $this->packageDataManager->addPackageUsage($package);
        });
        return $prom;
    }

    /**
     * @param PackageInterface $package
     */
    protected function createPackageVendorSymlink(PackageInterface $package)
    {
        $this->filesystem->ensureSymlinkExists(
            $this->getSymlinkSourcePath($package),
            $this->getPackageVendorSymlink($package)
        );

        $this->io->write('  - Symlinking <info>' . $this->getSymlinkSourcePath($package)
            . '</info> to (<fg=yellow>' . $this->getPackageVendorSymlink($package) . '</fg=yellow>)');

        // Clean up after ourselves in Composer 2+
        $vendorDirJunk = rtrim($this->composer->getConfig()->get('vendor-dir'))
            . DIRECTORY_SEPARATOR . $package->getPrettyName();
        try {
            rmdir($vendorDirJunk);
            rmdir(dirname($vendorDirJunk));
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @return string
     */
    protected function getSymlinkSourcePath(PackageInterface $package)
    {
        if (null != $this->config->getSymlinkBasePath()) {
            $targetDir = $package->getTargetDir();
            $sourcePath =
                $this->config->getSymlinkBasePath()
                . '/' . $package->getPrettyName() . '-' . $package->getVersion()
                . ($targetDir ? '/' . $targetDir : '');
        } else {
            $sourcePath = $this->getInstallPath($package);
        }

        return $sourcePath . '/';
    }
}

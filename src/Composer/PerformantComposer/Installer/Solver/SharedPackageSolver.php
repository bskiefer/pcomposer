<?php

namespace Composer\PerformantComposer\Installer\Solver;

use Composer\Package\PackageInterface;
use Composer\PerformantComposer\Installer\Config\SharedPackageInstallerConfig;
use Composer\PerformantComposer\Installer\SharedPackageInstaller;

class SharedPackageSolver
{
    /**
     * @var array
     */
    protected $packageCallbacks = array();

    /**
     * @var bool
     */
    protected $areAllShared = false;


    /**
     * @param SharedPackageInstallerConfig $config
     */
    public function __construct(SharedPackageInstallerConfig $config)
    {
        $packageList = $config->getPackageList();

        foreach ($packageList as $packageName) {
            if ('*' === $packageName) {
                $this->areAllShared = true;
            }
        }

        if (!$this->areAllShared) {
            $this->packageCallbacks = $this->createCallbacks($packageList);
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @return bool
     */
    public function isSharedPackage(PackageInterface $package)
    {
        $prettyName = $package->getPrettyName();

        // Avoid putting this package into dependencies folder, because on the first installation the package won't be
        // installed in dependencies folder but in the vendor folder.
        // So I prefer keeping this behavior for further installs.
        if (SharedPackageInstaller::PACKAGE_PRETTY_NAME === $prettyName) {
            return false;
        }

        if ($this->areAllShared || SharedPackageInstaller::PACKAGE_TYPE === $package->getType()) {
            return true;
        }

        foreach ($this->packageCallbacks as $equalityCallback) {
            if ($equalityCallback($prettyName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $packageList
     *
     * @return array
     */
    protected function createCallbacks(array $packageList)
    {
        $callbacks = array();

        foreach ($packageList as $packageName) {
            // Has wild card (*)
            if (false !== strpos($packageName, '*')) {
                $pattern = str_replace('*', '[a-zA-Z0-9-_]+', str_replace('/', '\/', $packageName));

                $callbacks[] = function ($packagePrettyName) use ($pattern) {
                    return 1 === preg_match('/' . $pattern . '/', $packagePrettyName);
                };
            // Raw package name
            } else {
                $callbacks[] = function ($packagePrettyName) use ($packageName) {
                    return $packageName === $packagePrettyName;
                };
            }
        }

        return $callbacks;
    }
}

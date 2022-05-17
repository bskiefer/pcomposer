<?php

namespace Composer\PerformantComposer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\PerformantComposer\Package\SharedPackageDataManager;
use Composer\PerformantComposer\Installer\Config\SharedPackageInstallerConfig;
use Composer\PerformantComposer\Installer\Filesystem\SymlinkFilesystem;

class LibraryPlugin implements PluginInterface
{
    private $installer;

    public function activate(Composer $composer, IOInterface $io)
    {
        $config = $this->setConfig($composer);
        $this->installer = new LibraryInstaller($io, $composer, new SymlinkFilesystem(), new SharedPackageDataManager($composer), $config);
        $composer->getInstallationManager()->addInstaller($this->installer);
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        $composer->getInstallationManager()->removeInstaller($this->installer);
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }

    protected function setConfig(Composer $composer)
    {
        $cfg = new SharedPackageInstallerConfig(
            $composer->getConfig()->get('vendor-dir'),
            $composer->getConfig()->get('vendor-dir', 1)
        );


        if ($composer->getPluginManager()->getGlobalComposer()) {
            $extra = $composer->getPluginManager()->getGlobalComposer()->getConfig()->get('extra');
            $cfg->setExtra($extra);
        }

        return $cfg;
    }
}

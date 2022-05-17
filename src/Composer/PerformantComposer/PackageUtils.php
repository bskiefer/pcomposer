<?php

namespace Composer\PerformantComposer;

use Composer\Composer;
use Composer\Package\PackageInterface;

class PackageUtils
{

    const PACKAGE_TYPE = 'shared-package';
    const CUSTOM_CONFIG = 'pcomposer';

    public static function getPackageInstallPath(PackageInterface $package, Composer $composer, array $pExtra = [])
    {
        $prettyName = $package->getPrettyName();
        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
        } else {
            $vendor = '';
            $name = $prettyName;
        }

        $availableVars = compact('name', 'vendor');

        $exclude = self::isPackageExcluded($package, self::getCustomExtraConfig($composer));

        if (count($pExtra) === 0 || $exclude) {
            $rootPath = 'vendor';
            $path = $rootPath . '/' . $package->getName();
        } else {
            $rootPath = $pExtra[self::PACKAGE_TYPE]['vendor-dir'];
            $path = $rootPath . '/' . $package->getName() . '-' . $package->getVersion();
        }


        return $path;
    }

    public static function getCustomExtraConfig(Composer $composer)
    {
        $cfg = $composer->getConfig()->all();
        if (isset($cfg['config']) && isset($cfg['config']['extra'])) {
            return $cfg['config']['extra'] ?? [];
        }

        return [];
    }

    public static function isPackageExcluded(PackageInterface $package, array $extra = [], ?Composer $composer = null)
    {
        if (count($extra) === 0 && $composer) {
            $extra = self::getCustomExtraConfig($composer);
        }

        if (isset($extra['pcomposer']) && isset($extra['pcomposer']['exclude'])) {
            $exclude = $extra['pcomposer']['exclude'] ?? [];
            return in_array($package->getName(), $exclude);
        }

        return false;
    }
}

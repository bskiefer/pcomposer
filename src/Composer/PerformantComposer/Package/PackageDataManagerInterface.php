<?php

namespace Composer\PerformantComposer\Package;

use Composer\Package\PackageInterface;

interface PackageDataManagerInterface
{
    /**
     * Add a row in the "packages.json" file, with the project name for the "package/version" key
     *
     * @param PackageInterface $package
     */
    public function addPackageUsage(PackageInterface $package);

    /**
     * Remove the row in the "packages.json" file
     *
     * @param PackageInterface $package
     */
    public function removePackageUsage(PackageInterface $package);

    /**
     * Return usage of the current package
     *
     * @param PackageInterface $package
     *
     * @return array
     */
    public function getPackageUsage(PackageInterface $package);

    /**
     * @param PackageInterface $package
     */
    public function setPackageInstallationSource(PackageInterface $package);

    /**
     * Set the vendor directory to save the "packages.json" file
     *
     * @param string $vendorDir
     */
    public function setVendorDir($vendorDir);
}

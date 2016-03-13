<?php

namespace Dms\Package\Analytics;

use Dms\Core\Package\Definition\PackageDefinition;
use Dms\Core\Package\Package;

/**
 * The analytics package
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsPackage extends Package
{
    /**
     * Defines the structure of this cms package.
     *
     * @param PackageDefinition $package
     *
     * @return void
     */
    protected function define(PackageDefinition $package)
    {
        $package->name('analytics');
        $package->dashboard()
            ->widgets(['config.*']);

        $package->modules([
            'config' => AnalyticsConfigModule::class
        ]);
    }
}
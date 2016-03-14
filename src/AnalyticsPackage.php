<?php

namespace Dms\Package\Analytics;

use Dms\Core\ICms;
use Dms\Core\Ioc\IIocContainer;
use Dms\Core\Package\Definition\PackageDefinition;
use Dms\Core\Package\Package;
use Dms\Package\Analytics\Persistence\DbAnalyticsDriverConfigRepository;

/**
 * The analytics package
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsPackage extends Package
{
    /**
     * Boots the package
     *
     * @param ICms $cms
     *
     * @return void
     */
    public static function boot(ICms $cms)
    {
        $cms->getLang()->addResourceDirectory('package.analytics', __DIR__ . '/../resources/lang/');

        $cms->getIocContainer()->bind(
            IIocContainer::SCOPE_SINGLETON,
            IAnalyticsDriverConfigRepository::class, DbAnalyticsDriverConfigRepository::class
        );
    }

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
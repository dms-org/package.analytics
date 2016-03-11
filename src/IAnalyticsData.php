<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Core\Module\Definition\ModuleDefinition;

/**
 * The analytics data interface
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IAnalyticsData
{
    /**
     * @param ModuleDefinition $module
     *
     * @return void
     */
    public function registerWidgets(ModuleDefinition $module);
}
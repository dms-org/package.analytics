<?php

namespace Dms\Package\Analytics\Persistence;

use Dms\Core\Persistence\Db\Mapping\Definition\Orm\OrmDefinition;
use Dms\Core\Persistence\Db\Mapping\Orm;
use Dms\Package\Analytics\AnalyticsDriverConfiguration;

/**
 * The orm for the analytics package.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsOrm extends Orm
{
    /**
     * Defines the object mappers registered in the orm.
     *
     * @param OrmDefinition $orm
     *
     * @return void
     */
    protected function define(OrmDefinition $orm)
    {
        $orm->entities([
            AnalyticsDriverConfiguration::class => AnalyticsDriverConfigurationMapper::class,
        ]);
    }
}
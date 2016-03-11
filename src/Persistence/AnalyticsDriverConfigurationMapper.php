<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Persistence;
use Dms\Core\Form\Object\FormObject;
use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;
use Dms\Package\Analytics\AnalyticsDriverConfiguration;
use Dms\Package\Analytics\AnalyticsDriverFactory;

/**
 * The analytics driver configuration mapper.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverConfigurationMapper extends EntityMapper
{
    /**
     * Defines the entity mapper
     *
     * @param MapperDefinition $map
     *
     * @return void
     */
    protected function define(MapperDefinition $map)
    {
        $map->type(AnalyticsDriverConfiguration::class);
        $map->toTable('analytics');

        $map->idToPrimaryKey('id');

        $map->property(AnalyticsDriverConfiguration::DRIVER_NAME)->to('driver')->asVarchar(255);
        $map->property(AnalyticsDriverConfiguration::OPTIONS)
            ->mappedVia(function (FormObject $options) : string {
                return json_encode($options->unprocess($options->getInitialValues()));
            }, function (string $json, array $row) : FormObject {
                return AnalyticsDriverFactory::load($row['driver'])
                    ->getOptionsForm()
                    ->submit(json_decode($json, true));
            })
            ->to('options')
            ->asText();
    }
}
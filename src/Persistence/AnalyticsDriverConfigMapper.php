<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Persistence;
use Dms\Core\Form\Object\FormObject;
use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;
use Dms\Package\Analytics\AnalyticsDriverConfig;
use Dms\Package\Analytics\AnalyticsDriverFactory;

/**
 * The analytics driver configuration mapper.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverConfigMapper extends EntityMapper
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
        $map->type(AnalyticsDriverConfig::class);
        $map->toTable('analytics');

        $map->idToPrimaryKey('id');

        $map->property(AnalyticsDriverConfig::DRIVER_NAME)->to('driver')->asVarchar(255);
        $map->property(AnalyticsDriverConfig::OPTIONS)
            ->mappedVia(function (FormObject $options) : string {
                $values = $options->getInitialValues();
                $values['__class'] = get_class($options);

                return json_encode($values);
            }, function (string $json) : FormObject {
                $values = json_decode($json, true);

                return (new $values['__class'])->withInitialValues(array_diff_key($values, ['__class' => true]));
            })
            ->to('options')
            ->asText();
    }
}
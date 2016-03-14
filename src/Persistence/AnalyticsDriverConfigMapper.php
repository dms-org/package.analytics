<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Persistence;

use Dms\Common\Structure\FileSystem\File;
use Dms\Core\File\IFile;
use Dms\Core\File\UploadedFileProxy;
use Dms\Core\Form\Object\FormObject;
use Dms\Core\Persistence\Db\Mapping\Definition\MapperDefinition;
use Dms\Core\Persistence\Db\Mapping\EntityMapper;
use Dms\Package\Analytics\AnalyticsDriverConfig;

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
                $values            = $this->transformFilesToArrays($options->unprocess($options->getInitialValues()));
                $values['__class'] = get_class($options);

                return json_encode($values);
            }, function (string $json) : FormObject {
                $values = $this->restoreFilesFromStrings(json_decode($json, true));

                return $values['__class']::build(array_diff_key($values, ['__class' => true]));
            })
            ->to('options')
            ->asText();
    }

    private function transformFilesToArrays(array $data) : array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->transformFilesToArrays($value);
            } elseif ($value instanceof IFile) {
                $data[$key] = [
                    '__is_proxy'         => $value instanceof UploadedFileProxy,
                    '__file_path'        => $value->getFullPath(),
                    '__file_client_name' => $value->getClientFileName(),
                ];
            }
        }

        return $data;
    }

    private function restoreFilesFromStrings(array $data) : array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['__file_path'])) {
                    $file = new File($value['__file_path'], $value['__file_client_name'] ?? null);

                    $data[$key] = $value['__is_proxy'] ? new UploadedFileProxy($file) : $file;
                } else {
                    $data[$key] = $this->restoreFilesFromStrings($value);
                }
            }
        }

        return $data;
    }
}
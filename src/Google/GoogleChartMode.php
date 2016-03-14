<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Core\Model\Object\Enum;
use Dms\Core\Model\Object\PropertyTypeDefiner;

/**
 * The google chart mode enum.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleChartMode extends Enum
{
    const CITY = 'city';
    const COUNTRY = 'country';

    /**
     * Defines the type of the options contained within the enum.
     *
     * @param PropertyTypeDefiner $values
     *
     * @return void
     */
    protected function defineEnumValues(PropertyTypeDefiner $values)
    {
        $values->asString();
    }
}
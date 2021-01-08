<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\Field;
use Dms\Common\Structure\FileSystem\File;
use Dms\Common\Structure\FileSystem\InMemoryFile;
use Dms\Common\Structure\Geo\Country;
use Dms\Core\File\IUploadedFile;
use Dms\Core\Form\Object\FormObjectDefinition;
use Dms\Core\Form\Object\IndependentFormObject;
use Dms\Core\Model\Type\Builder\Type;

/**
 * The google analytics form
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsForm extends IndependentFormObject
{
    /**
     * @var string
     */
    public $jsonServiceAccountKey;

    /**
     * @var int
     */
    public $viewId;

    /**
     * @var string
     */
    public $trackingCode;

    /**
     * @var GoogleChartMode
     */
    public $locationChartMode;

    /**
     * @var Country|null
     */
    public $mapCountry;

    /**
     * Defines the structure of the form object.
     *
     * @param FormObjectDefinition $form
     *
     * @return void
     */
    protected function defineForm(FormObjectDefinition $form)
    {
        $form->section('Account Details', [
            $form->bind($this->jsonServiceAccountKey)->to(
                Field::create('service_account_key', 'Service Account Key (*.json)')
                    ->file()->required()
                    ->extension('json')
                    ->map(function (IUploadedFile $file) {
                        return file_get_contents($file->getFullPath());
                    }, function (string $data) {
                        return new InMemoryFile($data, 'key.json');
                    }, Type::string())
            ),
            //
            $form->bind($this->viewId)->to(
                Field::create('view_id', 'View ID')
                    ->int()->required()
            ),
        ]);

        $form->section('Dashboard', [
            $form->bind($this->locationChartMode)->to(
                Field::create('location_chart_mode', 'Analytics Map Mode')
                    ->enum(GoogleChartMode::class, [
                        GoogleChartMode::CITY    => 'Cities',
                        GoogleChartMode::COUNTRY => 'Countries',
                    ])
            ),
            //
            $form->bind($this->mapCountry)->to(
                Field::create('map_country', 'Analytics Map Country')
                    ->enum(Country::class, Country::getNameMap())
            ),
        ]);

        $form->section('Embed', [
            //
            $form->bind($this->trackingCode)->to(
                Field::create('tracking_code', 'UA tracking code')
                    ->string()->required()
            ),
        ]);
    }
}
<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\Field;
use Dms\Common\Structure\FileSystem\File;
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
    public $serviceAccountEmail;

    /**
     * @var string
     */
    public $privateKeyData;

    /**
     * @var int
     */
    public $viewId;

    /**
     * @var string
     */
    public $trackingCode;

    /**
     * Defines the structure of the form object.
     *
     * @param FormObjectDefinition $form
     *
     * @return void
     */
    protected function defineForm(FormObjectDefinition $form)
    {
        $form->section('Details', [
            $form->bind($this->serviceAccountEmail)->to(
                Field::create('service_account_email', 'Service Account Email')
                    ->string()->email()->required()
            ),
            //
            $form->bind($this->privateKeyData)->to(
                Field::create('private_key_data', 'Private Key (*.p12)')
                    ->file()->required()
                    ->map(function (IUploadedFile $file) {
                        return base64_encode(file_get_contents($file->getFullPath()));
                    }, function (string $data) {
                        return File::createInMemory(base64_decode($data), 'key.p12');
                    }, Type::string())
            ),
            //
            $form->bind($this->viewId)->to(
                Field::create('view_id', 'View ID')
                    ->int()->required()
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
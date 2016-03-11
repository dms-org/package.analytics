<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Core\Form\Object\FormObjectDefinition;
use Dms\Core\Form\Object\IndependentFormObject;

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
            $form->field($this->serviceAccountEmail)
                ->name('service_account_email')
                ->label('Service Account Email')
                ->string()->email()->required(),
            //
            $form->field($this->privateKeyData)
                ->name('private_key_data')
                ->label('Private Key (*.p12)')
                ->string()->multiline()->required(),
            //
            $form->field($this->viewId)
                ->name('view_id')
                ->label('View ID')
                ->int()->required(),
        ]);

        $form->section('Embed', [
            //
            $form->field($this->trackingCode)
                ->name('tracking_code')
                ->label('UA tracking code')
                ->string()->required(),
        ]);
    }
}
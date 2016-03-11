<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Common\Structure\Field;
use Dms\Core\Auth\IAuthSystem;
use Dms\Core\Common\Crud\CrudModule;
use Dms\Core\Common\Crud\Definition\CrudModuleDefinition;
use Dms\Core\Common\Crud\Definition\Form\CrudFormDefinition;
use Dms\Core\Common\Crud\Definition\Table\SummaryTableDefinition;

/**
 * The analytics configuration module
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsConfigurationModule extends CrudModule
{
    /**
     * @var IAnalyticsDriverConfigurationRepository
     */
    protected $dataSource;

    /**
     * @inheritDoc
     */
    public function __construct(IAnalyticsDriverConfigurationRepository $dataSource, IAuthSystem $authSystem)
    {
        parent::__construct($dataSource, $authSystem);
    }

    /**
     * Defines the structure of this module.
     *
     * @param CrudModuleDefinition $module
     */
    protected function defineCrudModule(CrudModuleDefinition $module)
    {
        $module->name('config');

        $module->labelObjects()->fromCallback(function (AnalyticsDriverConfiguration $driverConfig) {
            return $driverConfig->getDriver()->getLabel();
        });

        $module->crudForm(function (CrudFormDefinition $form) {
            $form->section('Details', [
                $form->field(
                    Field::create('type', 'Type')->string()->oneOf(AnalyticsDriverFactory::getDriverOptions())->required()
                )->bindToProperty(AnalyticsDriverConfiguration::DRIVER_NAME)
            ]);

            $form->dependentOn(['type'], function (CrudFormDefinition $form, array $input, AnalyticsDriverConfiguration $driverConfig = null) {
                if ($driverConfig && $driverConfig->driverName === $input['type']) {
                    $optionsForm = $driverConfig->options;
                } else {
                    $optionsForm = AnalyticsDriverFactory::load($input['type'])->getOptionsForm();
                }

                $form->continueSection([
                    $form->field(
                        Field::create('options', 'Options')->form($optionsForm)->required()
                    )->bindToProperty(AnalyticsDriverConfiguration::OPTIONS)
                ]);
            });
        });

        $module->removeAction()->deleteFromDataSource();

        $module->summaryTable(function (SummaryTableDefinition $table) {
            $table->mapProperty(AnalyticsDriverConfiguration::DRIVER_NAME)->to(Field::create('name', 'Name')->string());
        });

        foreach ($this->dataSource->getAll() as $driverConfig) {
            $driverConfig->getAnalyticsData()->registerWidgets($module);
        }
    }
}
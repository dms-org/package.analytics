<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Common\Structure\Field;
use Dms\Core\Auth\IAuthSystem;
use Dms\Core\Common\Crud\CrudModule;
use Dms\Core\Common\Crud\Definition\CrudModuleDefinition;
use Dms\Core\Common\Crud\Definition\Form\CrudFormDefinition;
use Dms\Core\Common\Crud\Definition\Table\SummaryTableDefinition;
use Dms\Core\Form\Object\FormObject;

/**
 * The analytics configuration module
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsConfigModule extends CrudModule
{
    /**
     * @var IAnalyticsDriverConfigRepository
     */
    protected $dataSource;

    /**
     * @var AnalyticsDriverFactory
     */
    protected $driverFactory;

    /**
     * @inheritDoc
     */
    public function __construct(
        IAnalyticsDriverConfigRepository $dataSource,
        IAuthSystem $authSystem,
        AnalyticsDriverFactory $driverFactory
    ) {
        $this->driverFactory = $driverFactory;
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

        $module->labelObjects()->fromCallback(function (AnalyticsDriverConfig $driverConfig) {
            return $this->driverFactory->load($driverConfig->driverName)->getLabel();
        });

        $module->crudForm(function (CrudFormDefinition $form) {
            $form->section('Details', [
                $form->field(
                    Field::create('type', 'Type')->string()->oneOf($this->driverFactory->getDriverOptions())->required()
                )->bindToProperty(AnalyticsDriverConfig::DRIVER_NAME)
            ]);

            $form->dependentOn(['type'],
                function (CrudFormDefinition $form, array $input, AnalyticsDriverConfig $driverConfig = null) {
                    if ($driverConfig && $driverConfig->driverName === $input['type']) {
                        $optionsForm = $driverConfig->options;
                    } else {
                        $optionsForm = $this->driverFactory->load($input['type'])->getOptionsForm();
                    }

                    $form->continueSection([
                        $form->field(
                            Field::create('options', 'Options')
                                ->form($optionsForm)
                                ->required()
                                ->assert(function (FormObject $options) use ($input) {
                                    return $this->driverFactory->load($input['type'])->validate($options);
                                }, 'package.analytics::validation.api-details-failure')
                        )->bindToProperty(AnalyticsDriverConfig::OPTIONS)
                    ]);
                });
        });

        $module->removeAction()->deleteFromDataSource();

        $module->summaryTable(function (SummaryTableDefinition $table) {
            $table->mapCallback(function (AnalyticsDriverConfig $driverConfig) {
                return $this->driverFactory->load($driverConfig->driverName)->getLabel();
            })->to(Field::create('name', 'Name')->string());
        });

        foreach ($this->dataSource->getAll() as $driverConfig) {
            $this->driverFactory->load($driverConfig->driverName)
                ->getAnalyticsData($driverConfig->options)
                ->registerWidgets($module);
        }
    }
}
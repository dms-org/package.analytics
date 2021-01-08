<?php

namespace Dms\Package\Analytics\Tests;

use Dms\Common\Structure\FileSystem\File;
use Dms\Common\Structure\FileSystem\InMemoryFile;
use Dms\Core\Auth\IPermission;
use Dms\Core\Auth\Permission;
use Dms\Core\Common\Crud\Action\Object\IObjectAction;
use Dms\Core\Common\Crud\ICrudModule;
use Dms\Core\File\UploadedFileProxy;
use Dms\Core\Ioc\IIocContainer;
use Dms\Core\Model\IMutableObjectSet;
use Dms\Core\Persistence\ArrayRepository;
use Dms\Core\Tests\Common\Crud\Modules\CrudModuleTest;
use Dms\Core\Tests\Helpers\Mock\MockingIocContainer;
use Dms\Core\Tests\Module\Mock\MockAuthSystem;
use Dms\Package\Analytics\AnalyticsConfigModule;
use Dms\Package\Analytics\AnalyticsDriverConfig;
use Dms\Package\Analytics\AnalyticsDriverFactory;
use Dms\Package\Analytics\Google\GoogleAnalyticsDriver;
use Dms\Package\Analytics\Google\GoogleAnalyticsForm;
use Dms\Package\Analytics\IAnalyticsDriverConfigRepository;
use Interop\Container\ContainerInterface;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsConfigModuleTest extends CrudModuleTest
{

    /**
     * @return IMutableObjectSet
     */
    protected function buildRepositoryDataSource() : IMutableObjectSet
    {
        return new class(AnalyticsDriverConfig::collection()) extends ArrayRepository implements IAnalyticsDriverConfigRepository
        {

        };
    }

    /**
     * @param IMutableObjectSet $dataSource
     * @param MockAuthSystem    $authSystem
     *
     * @return ICrudModule
     */
    protected function buildCrudModule(IMutableObjectSet $dataSource, MockAuthSystem $authSystem) : ICrudModule
    {
        return new AnalyticsConfigModule($dataSource, $authSystem, new AnalyticsDriverFactory($this->mockIocContainer()));
    }

    protected function mockIocContainer() : ContainerInterface
    {
        $container = $this->getMockForAbstractClass(IIocContainer::class);

        $driver = $this->getMockBuilder(GoogleAnalyticsDriver::class)->setMethods(['validate'])->getMock();

        $driver->method('validate')->willReturn(true);

        $container->expects(self::once())
            ->method('get')
            ->with(GoogleAnalyticsDriver::class)
            ->willReturn($driver);

        return $container;
    }

    /**
     * @return string
     */
    protected function expectedName()
    {
        return 'config';
    }

    /**
     * @return IPermission[]
     */
    protected function expectedReadModuleRequiredPermissions()
    {
        return [
            Permission::named(ICrudModule::VIEW_PERMISSION),
        ];
    }

    /**
     * @return IPermission[]
     */
    protected function expectedReadModulePermissions()
    {
        return [
            Permission::named(ICrudModule::CREATE_PERMISSION),
            Permission::named(ICrudModule::EDIT_PERMISSION),
            Permission::named(ICrudModule::REMOVE_PERMISSION),
        ];
    }

    public function testCreate()
    {
        $this->module->getCreateAction()->run([
            'type'    => 'google',
            'options' => [
                'service_account_key'      => [
                    'file' => new UploadedFileProxy(new InMemoryFile('abc123', 'some-name.json')),
                    'action' => 'store-new',
                ],
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ]
        ]);

        $driverConfig = new AnalyticsDriverConfig('google', GoogleAnalyticsForm::build([
            'service_account_key'      => [
                'file' => new UploadedFileProxy(new InMemoryFile('abc123', 'some-name.json')),
                'action' => 'store-new',
            ],
            'view_id'               => 123456,
            'tracking_code'         => 'UA-XXXXXX-Y',
        ]));
        $driverConfig->setId(1);

        $this->assertSame(1, $this->dataSource->count());
        $this->assertEquals($driverConfig, $this->dataSource->get(1));
    }

    public function testEdit()
    {
        $this->testCreate();

        $form = $this->module->getEditAction()->getStagedForm()->submitFirstStage([
            IObjectAction::OBJECT_FIELD_NAME => 1,
        ]);

        $this->assertSame(['type' => 'google'], $form->getFormForStage(1, [])->getInitialValues());
        $this->assertEquals([
            'installation_instructions' => (new GoogleAnalyticsDriver())->getInstallationInstructions(),
            'options' => GoogleAnalyticsForm::build([
                'service_account_key'      => [
                    'file' => new UploadedFileProxy(new InMemoryFile('abc123', 'some-name.json')),
                    'action' => 'store-new',
                ],
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ])
        ], $form->getFormForStage(2, ['type' => 'google'])->getInitialValues());
    }
}
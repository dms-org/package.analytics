<?php

namespace Dms\Package\Analytics\Tests;

use Dms\Common\Structure\FileSystem\File;
use Dms\Core\Auth\IPermission;
use Dms\Core\Auth\Permission;
use Dms\Core\Common\Crud\Action\Object\IObjectAction;
use Dms\Core\Common\Crud\ICrudModule;
use Dms\Core\File\UploadedFileProxy;
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
        $container = $this->getMockForAbstractClass(ContainerInterface::class);

        $container->expects(self::once())
            ->method('get')
            ->with(GoogleAnalyticsDriver::class)
            ->willReturn(new GoogleAnalyticsDriver());

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
                'service_account_email' => 'some@email.com',
                'private_key_data'      => [
                    'file'   => new UploadedFileProxy(File::createInMemory('abc123')),
                    'action' => 'store-new',
                ],
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ]
        ]);

        $driverConfig = new AnalyticsDriverConfig('google', GoogleAnalyticsForm::build([
            'service_account_email' => 'some@email.com',
            'private_key_data'      => [
                'file'   => new UploadedFileProxy(File::createInMemory('abc123')),
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
            'options' => GoogleAnalyticsForm::build([
                'service_account_email' => 'some@email.com',
                'private_key_data'      => [
                    'file'   => new UploadedFileProxy(File::createInMemory('abc123')),
                    'action' => 'store-new',
                ],
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ])
        ], $form->getFormForStage(2, ['type' => 'google'])->getInitialValues());
    }
}
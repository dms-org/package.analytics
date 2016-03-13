<?php

namespace Dms\Package\Analytics\Tests\Persistence;

use Dms\Common\Structure\FileSystem\File;
use Dms\Core\File\UploadedFileProxy;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Tests\Persistence\Db\Integration\Mapping\DbIntegrationTest;
use Dms\Package\Analytics\AnalyticsDriverConfig;
use Dms\Package\Analytics\Google\GoogleAnalyticsForm;
use Dms\Package\Analytics\Persistence\AnalyticsDriverConfigRepository;
use Dms\Package\Analytics\Persistence\AnalyticsOrm;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverConfigTest extends DbIntegrationTest
{
    /**
     * @return IOrm
     */
    protected function loadOrm()
    {
        return new AnalyticsOrm();
    }

    public function setUp()
    {
        parent::setUp();
        $this->repo = new AnalyticsDriverConfigRepository($this->connection, $this->orm);
    }

    public function testPersistence()
    {
        $driverConfig = new AnalyticsDriverConfig('google', GoogleAnalyticsForm::build([
            'service_account_email' => 'some@email.com',
            'private_key_data'      => [
                'file'   => new UploadedFileProxy(File::createInMemory('abc123')),
                'action' => 'store-new',
            ],
            'view_id'               => 123456,
            'tracking_code'         => 'UA-XXXXXX-Y',
        ]));

        $this->repo->save($driverConfig);

        $this->assertDatabaseDataSameAs([
            'analytics' => [
                [
                    'id'      => 1,
                    'driver'  => 'google',
                    'options' => json_encode([
                        'service_account_email' => 'some@email.com',
                        'private_key_data'      => base64_encode('abc123'),
                        'view_id'               => 123456,
                        'tracking_code'         => 'UA-XXXXXX-Y',
                        '__class'               => GoogleAnalyticsForm::class,
                    ])
                ]
            ]
        ]);

        $loadedDriverConfig = $this->repo->get(1);

        $this->assertNotSame($driverConfig, $loadedDriverConfig);
        $this->assertEquals($driverConfig, $loadedDriverConfig);
    }
}
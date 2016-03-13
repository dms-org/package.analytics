<?php

namespace Dms\Package\Analytics\Tests;

use Dms\Common\Structure\FileSystem\File;
use Dms\Common\Testing\CmsTestCase;
use Dms\Core\File\UploadedFileProxy;
use Dms\Core\Persistence\ArrayRepository;
use Dms\Core\Tests\Helpers\Mock\MockingIocContainer;
use Dms\Package\Analytics\AnalyticsDriverConfig;
use Dms\Package\Analytics\AnalyticsDriverFactory;
use Dms\Package\Analytics\AnalyticsEmbedCodeService;
use Dms\Package\Analytics\Google\GoogleAnalyticsForm;
use Dms\Package\Analytics\IAnalyticsDriverConfigRepository;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsEmbedCodeServiceTest extends CmsTestCase
{
    protected function dataSource()
    {
        $drivers = [
            new AnalyticsDriverConfig('google', GoogleAnalyticsForm::build([
                'service_account_email' => 'some@email.com',
                'private_key_data'      => [
                    'file' => new UploadedFileProxy(File::createInMemory('abc123')),
                    'action' => 'store-new',
                ],
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ]))
        ];

        return new class(AnalyticsDriverConfig::collection($drivers)) extends ArrayRepository implements IAnalyticsDriverConfigRepository
        {

        };
    }

    public function testEmbedCode()
    {
        $service = new AnalyticsEmbedCodeService($this->dataSource(), new AnalyticsDriverFactory(new MockingIocContainer($this)));

        $this->assertContains('UA-XXXXXX-Y', $service->getEmbedCode());
    }
}
<?php

namespace Dms\Package\Analytics\Tests;

use Dms\Common\Testing\CmsTestCase;
use Dms\Core\Persistence\ArrayRepository;
use Dms\Package\Analytics\AnalyticsDriverConfiguration;
use Dms\Package\Analytics\AnalyticsEmbedCodeService;
use Dms\Package\Analytics\Google\GoogleAnalyticsForm;
use Dms\Package\Analytics\IAnalyticsDriverConfigurationRepository;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsEmbedCodeServiceTest extends CmsTestCase
{
    protected function dataSource()
    {
        $drivers = [
            new AnalyticsDriverConfiguration('google', GoogleAnalyticsForm::build([
                'service_account_email' => 'some@email.com',
                'private_key_data'      => 'abc123',
                'view_id'               => 123456,
                'tracking_code'         => 'UA-XXXXXX-Y',
            ]))
        ];

        return new class(AnalyticsDriverConfiguration::collection($drivers)) extends ArrayRepository implements IAnalyticsDriverConfigurationRepository
        {

        };
    }

    public function testEmbedCode()
    {
        $service = new AnalyticsEmbedCodeService($this->dataSource());

        $this->assertContains('UA-XXXXXX-Y', $service->getEmbedCode());
    }
}
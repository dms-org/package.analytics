<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Tests\Google;

use Dms\Common\Testing\CmsTestCase;
use Dms\Package\Analytics\Google\GoogleAnalyticsDriver;
use Dms\Package\Analytics\Google\GoogleAnalyticsForm;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsDriverTest extends CmsTestCase
{
    /**
     * @var GoogleAnalyticsDriver
     */
    protected $driver;

    public function setUp()
    {
        $this->driver = new GoogleAnalyticsDriver();
    }

    public function testOptions()
    {
        /** @var GoogleAnalyticsForm $form */
        $form = $this->driver->getOptionsForm();

        $this->assertInstanceOf(GoogleAnalyticsForm::class, $form);
    }

    public function testEmbedCode()
    {
        /** @var GoogleAnalyticsForm $form */
        $form = $this->driver->getOptionsForm();
        $form->trackingCode = 'UA-234343-Y';

        $this->assertContains('UA-234343-Y', $this->driver->getEmbedCode($form));
    }
}
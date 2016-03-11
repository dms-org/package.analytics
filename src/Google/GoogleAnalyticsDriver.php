<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\Object\FormObject;
use Dms\Package\Analytics\IAnalyticsData;
use Dms\Package\Analytics\IAnalyticsDriver;
use Google_Client;
use Google_Service_Analytics;

/**
 * The google analytics driver
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsDriver implements IAnalyticsDriver
{
    /**
     * @inheritDoc
     */
    public function getName() : string
    {
        return 'google';
    }

    /**
     * @inheritDoc
     */
    public function getLabel() : string
    {
        return 'Google Analytics';
    }

    /**
     * @inheritDoc
     */
    public function getOptionsForm() : FormObject
    {
        return new GoogleAnalyticsForm();
    }

    /**
     * @inheritDoc
     */
    public function getAnalyticsData(FormObject $options) : IAnalyticsData
    {
        /** @var GoogleAnalyticsForm $options */
        InvalidArgumentException::verifyInstanceOf(__METHOD__, 'options', $options, GoogleAnalyticsForm::class);

        $credentials = new \Google_Auth_AssertionCredentials(
            $options->serviceAccountEmail,
            [Google_Service_Analytics::ANALYTICS_READONLY],
            $options->privateKeyData
        );

        $client = new Google_Client();
        $client->setApplicationName('dms.package.analytics');
        $client->setAssertionCredentials($credentials);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion();
        }

        return new GoogleAnalyticsData(new Google_Service_Analytics($client), $options->viewId);
    }

    /**
     * @inheritDoc
     */
    public function getEmbedCode(FormObject $options) : string
    {
        /** @var GoogleAnalyticsForm $options */
        InvalidArgumentException::verifyInstanceOf(__METHOD__, 'options', $options, GoogleAnalyticsForm::class);

        $code = json_encode($options->trackingCode);
        return <<<HTML
<!-- Google Analytics -->
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '{$code}', 'auto');
ga('send', 'pageview');
</script>
<!-- End Google Analytics -->
HTML;
    }
}
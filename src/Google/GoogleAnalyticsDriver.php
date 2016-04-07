<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\Web\Html;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\Object\FormObject;
use Dms\Package\Analytics\IAnalyticsData;
use Dms\Package\Analytics\IAnalyticsDriver;
use Google_Client;
use Google_Service_Analytics;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The google analytics driver
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsDriver implements IAnalyticsDriver
{
    /**
     * @var CacheItemPoolInterface|null
     */
    protected $cache;

    /**
     * GoogleAnalyticsDriver constructor.
     *
     * @param CacheItemPoolInterface|null $cache
     */
    public function __construct(CacheItemPoolInterface $cache = null)
    {
        $this->cache = $cache;
    }

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
    public function getInstallationInstructions() : Html
    {
        return new Html(<<<HTML
<p>This assumes you have a Google Analytics account set up for this site.</p>

<p>Create a project for this site (if one does not exist) under the <a href="https://console.developers.google.com/" target="_blank">google developer console</a>.</p>

<p>In the overview, go to "Analytics API" and click "Enable"</p>

<p>Under the credentials page create a "service account key" and ensure you select "New service account" and choose the *.p12 file format.</p>

<p>You will have to store the service account email and the private key (*.p12 file) and upload them here.</p>

<p>Now in <a href="https://analytics.google.com/" target="_blank">Google Analytics</a>, go to the "Admin" tab, select "View Settings" and copy the "View ID" here.</p>

<p>Under "User Management" enter the service account's email with "Read and Analyse" permissions and click "Add".</p>

<p>Now you should be able to complete this form.</p>
HTML
        );
    }

    /**
     * @return null|CacheItemPoolInterface
     */
    public function getCache() : CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @param CacheItemPoolInterface $cache
     */
    public function setCache(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public function getOptionsForm() : FormObject
    {
        return new GoogleAnalyticsForm();
    }

    public function validate(FormObject $options) : bool
    {
        /** @var GoogleAnalyticsForm $options */
        InvalidArgumentException::verifyInstanceOf(__METHOD__, 'options', $options, GoogleAnalyticsForm::class);

        try {
            (new Google_Service_Analytics($this->buildApiClient($options)))->data_ga->get(
                'ga:' . $options->viewId,
                'today',
                'today',
                'ga:sessions'
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getAnalyticsData(FormObject $options) : IAnalyticsData
    {
        /** @var GoogleAnalyticsForm $options */
        InvalidArgumentException::verifyInstanceOf(__METHOD__, 'options', $options, GoogleAnalyticsForm::class);

        $client = $this->buildApiClient($options);

        return new GoogleAnalyticsData(new Google_Service_Analytics($client), $options->viewId, $this->cache, $options->locationChartMode, $options->mapCountry);
    }

    /**
     * @param GoogleAnalyticsForm $options
     *
     * @return Google_Client
     */
    protected function buildApiClient(GoogleAnalyticsForm $options) : Google_Client
    {
        $credentials = new \Google_Auth_AssertionCredentials(
            $options->serviceAccountEmail,
            [Google_Service_Analytics::ANALYTICS_READONLY],
            base64_decode($options->privateKeyData)
        );

        $client = new Google_Client();
        $client->setApplicationName('dms.package.analytics');
        if ($this->cache) {
            $client->setCache(new GooglePsr6CacheAdapter($client, $this->cache));
        }
        $client->setAccessType('offline');

        $client->setAssertionCredentials($credentials);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion();

            return $client;
        }

        return $client;
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
<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Tests\Google;

use Dms\Common\Testing\CmsTestCase;
use Dms\Core\Table\ITableRow;
use Dms\Package\Analytics\Google\GoogleAnalyticsTableDataSource;
use Google_Service_Analytics_DataGa_Resource;

/**
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsTableDataSourceTest extends CmsTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientMock;

    /**
     * @var array
     */
    protected $responseData;

    /**
     * @var GoogleAnalyticsTableDataSource
     */
    protected $dataSource;

    public function setUp()
    {
        $this->clientMock   = $this->getMockWithoutInvokingTheOriginalConstructor(Google_Service_Analytics_DataGa_Resource::class, ['get']);
        $this->responseData = json_decode(file_get_contents(__DIR__ . '/data/response.json'), true);

        $this->dataSource = new GoogleAnalyticsTableDataSource($this->clientMock, 123456, 365);
    }

    public function testLoadRows()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->willReturn($this->responseData);

        $response = $this->dataSource->load();

        $this->assertCount(1, $response->getSections());
        $section = $response->getSections()[0];

        $this->assertSame(null, $section->getGroupData());
        $this->assertContainsOnlyInstancesOf(ITableRow::class, $section->getRows());
        $this->assertCount(85, $section->getRows());
    }

    public function testCountRows()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->willReturn($this->responseData);

        $response = $this->dataSource->count();

        $this->assertSame(85, $response);
    }

    public function testLoadNoCriteria()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->with(
                'ga:123456',
                '365daysAgo',
                'today',
                'ga:pageviews,ga:sessionDuration,ga:sessions',
                ['dimensions' => 'ga:date,ga:pagePath,ga:browser,ga:browserVersion,ga:city,ga:country', 'start-index' => 0, 'itemsPerPage' => 10000]
            )
            ->willReturn($this->responseData);

        $this->dataSource->load();
    }

    public function testCriteriaSimpleFilters()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->with(
                'ga:123456',
                '365daysAgo',
                'today',
                'ga:pageviews,ga:sessionDuration,ga:sessions',
                [
                    'dimensions' => 'ga:date,ga:pagePath,ga:browser,ga:browserVersion,ga:city,ga:country', 'start-index' => 0, 'itemsPerPage' => 10000,
                    'filter'     => 'ga:browser==Chrome;ga:browserVersion!=48',
                ]
            )
            ->willReturn($this->responseData);

        $this->dataSource->load(
            $this->dataSource->criteria()
                ->loadAll()
                ->where('browser.name', '=', 'Chrome')
                ->where('browser.version', '!=', '48')
        );
    }

    public function testCriteriaOrdering()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->with(
                'ga:123456',
                '365daysAgo',
                'today',
                'ga:pageviews,ga:sessionDuration,ga:sessions',
                [
                    'dimensions' => 'ga:date,ga:pagePath,ga:browser,ga:browserVersion,ga:city,ga:country', 'start-index' => 0, 'itemsPerPage' => 10000,
                    'sort'       => 'ga:browser,-ga:browserVersion',
                ]
            )
            ->willReturn($this->responseData);

        $this->dataSource->load(
            $this->dataSource->criteria()
                ->loadAll()
                ->orderByAsc('browser.name')
                ->orderByDesc('browser.version')
        );
    }
}
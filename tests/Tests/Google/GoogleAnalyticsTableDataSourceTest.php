<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Tests\Google;

use Dms\Common\Structure\Field;
use Dms\Common\Testing\CmsTestCase;
use Dms\Core\Table\Builder\Column;
use Dms\Core\Table\ITableRow;
use Dms\Package\Analytics\Google\GoogleAnalyticsTableDataSource;
use Google_Service_Analytics_Resource_DataGa;
use Google_Service_Analytics_GaData;

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

    public function setUp(): void
    {
        $this->clientMock   = $this->createMock(Google_Service_Analytics_Resource_DataGa::class);
        $this->responseData = new Google_Service_Analytics_GaData(json_decode(file_get_contents(__DIR__ . '/data/response.json'), true));

        $this->dataSource = new GoogleAnalyticsTableDataSource($this->clientMock, 123456, 365, [
            Column::from(Field::create('date', 'Date')->date()),
        ], ['ga:date' => 'date']);
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
        $this->assertCount(60, $section->getRows());
    }

    public function testCountRows()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->willReturn($this->responseData);

        $response = $this->dataSource->count();

        $this->assertSame(60, $response);
    }

    public function testLoadNoCriteria()
    {
        $this->clientMock->expects(self::once())
            ->method('get')
            ->with(
                'ga:123456',
                '365daysAgo',
                'today',
                'ga:pageviews,ga:sessions',
                [
                    'dimensions'  => 'ga:date',
                    'start-index' => 1,
                ]
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
                'ga:pageviews,ga:sessions',
                [
                    'dimensions'  => 'ga:date',
                    'start-index' => 1,
                    'filters'     => 'ga:sessions==10;ga:pageviews!=15',
                ]
            )
            ->willReturn($this->responseData);

        $this->dataSource->load(
            $this->dataSource->criteria()
                ->loadAll()
                ->where('statistics.sessions', '=', 10)
                ->where('statistics.page_views', '!=', 15)
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
                'ga:pageviews,ga:sessions',
                [
                    'dimensions'  => 'ga:date',
                    'start-index' => 1,
                    'sort'        => 'ga:date,-ga:date',
                ]
            )
            ->willReturn($this->responseData);

        $this->dataSource->load(
            $this->dataSource->criteria()
                ->loadAll()
                ->orderByAsc('date')
                ->orderByDesc('date')
        );
    }
}
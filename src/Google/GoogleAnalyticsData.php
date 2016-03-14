<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\DateTime\Date;
use Dms\Common\Structure\Field;
use Dms\Common\Structure\Geo\Chart\GeoCityChart;
use Dms\Common\Structure\Geo\Chart\GeoCountryChart;
use Dms\Common\Structure\Geo\Country;
use Dms\Core\Module\Definition\ModuleDefinition;
use Dms\Core\Module\ITableDisplay;
use Dms\Core\Module\Table\TableDisplay;
use Dms\Core\Table\Builder\Column;
use Dms\Core\Table\Chart\Criteria\ChartCriteria;
use Dms\Core\Table\Chart\DataSource\Definition\ChartTableMapperDefinition;
use Dms\Core\Table\Chart\Structure\ChartAxis;
use Dms\Core\Table\Chart\Structure\LineChart;
use Dms\Core\Table\Chart\Structure\PieChart;
use Dms\Core\Table\DataSource\ArrayTableDataSource;
use Dms\Core\Table\DataSource\Definition\GroupedTableDefinition;
use Dms\Package\Analytics\IAnalyticsData;
use Google_Service_Analytics;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The google analytics data class.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsData implements IAnalyticsData
{
    /**
     * @var Google_Service_Analytics
     */
    private $client;

    /**
     * @var int
     */
    private $viewId;

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var GoogleChartMode
     */
    private $chartMode;

    /**
     * @var Country
     */
    private $mapCountry;

    /**
     * GoogleAnalyticsData constructor.
     *
     * @param Google_Service_Analytics $client
     * @param int                      $viewId
     * @param CacheItemPoolInterface   $cache
     * @param GoogleChartMode          $chartMode
     * @param Country                  $mapCountry
     */
    public function __construct(
        Google_Service_Analytics $client,
        int $viewId,
        CacheItemPoolInterface $cache,
        GoogleChartMode $chartMode,
        Country $mapCountry = null
    ) {
        $this->client     = $client;
        $this->viewId     = $viewId;
        $this->cache      = $cache;
        $this->chartMode  = $chartMode;
        $this->mapCountry = $mapCountry;
    }

    /**
     * @inheritDoc
     */
    public function registerWidgets(ModuleDefinition $module)
    {
        $sessionAnalyticsTable = $this->loadAnalyticsWithBreakdown(
            'google-analytics-sessions',
            [
                Column::from(Field::create('date', 'Date')->date()),
            ],
            ['ga:date' => 'date']
        );

        $locationAnalyticsTable = $this->loadAnalyticsWithBreakdown(
            'google-analytics-location-city-breakdown',
            [
                Column::name('location')->label('Location')->components([
                    Field::create('city', 'City')->string()->required(),
                    Field::create('city_lat_lng', 'City Lat/Lng')->latLng()->required(),
                    Field::create('country', 'Country')->enum(Country::class, Country::getShortNameMap())->required(),
                ])
            ],
            [
                'ga:countryIsoCode' => 'location.country',
                'ga:city'           => 'location.city',
                'ga:latitude'       => 'location.city_lat_lng',
                'ga:longitude'      => 'location.city_lat_lng'
            ]
        );

        $browserAnalyticsTable = $this->loadAnalyticsWithBreakdown(
            'google-analytics-browser-version-breakdown',
            [
                Column::name('browser')->label('Browser')->components([
                    Field::create('name', 'Name')->string(),
                    Field::create('version', 'Version')->string(),
                ])
            ],
            ['ga:browser' => 'browser.name', 'ga:browserVersion' => 'browser.version']
        );

        $pageAnalyticsTable = $this->loadAnalyticsWithBreakdown(
            'google-analytics-page-breakdown',
            [
                Column::from(Field::create('page', 'Page')->string()),
            ],
            ['ga:pagePath' => 'page']
        );

        $module->custom()->table($sessionAnalyticsTable);
        $module->custom()->table($locationAnalyticsTable);
        $module->custom()->table($browserAnalyticsTable);
        $module->custom()->table($pageAnalyticsTable);

        $module->table('google-analytics-location-country-breakdown')
            ->fromPreviousTable('google-analytics-location-city-breakdown')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('location.country');

                $this->mapSessionStatistics($map);
            })
            ->withoutViews();

        $module->table('google-analytics-browser-breakdown')
            ->fromPreviousTable('google-analytics-browser-version-breakdown')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('browser.name');

                $this->mapSessionStatistics($map);
            })
            ->withoutViews();

        $module->chart('google-analytics-sessions')
            ->fromTable('google-analytics-sessions')
            ->map(function (ChartTableMapperDefinition $map) {
                $map->structure(new LineChart(
                    $map->column('date')->toAxis('date'),
                    new ChartAxis('statistics', 'Statistics', [
                        $map->column('statistics.sessions')->asComponent(),
                        $map->column('statistics.page_views')->asComponent(),
                    ])
                ));
            })
            ->withoutViews();

        $module->chart('google-analytics-browser-breakdown')
            ->fromTable('google-analytics-browser-breakdown')
            ->map(function (ChartTableMapperDefinition $map) {
                $map->structure(new PieChart(
                    $map->column('browser.name')->toAxis('browser', 'Browser'),
                    $map->column('statistics.sessions')->toAxis('sessions', 'Sessions')
                ));
            })
            ->withoutViews();

        $module->chart('google-analytics-location-country-breakdown')
            ->fromTable('google-analytics-location-country-breakdown')
            ->map(function (ChartTableMapperDefinition $map) {
                $map->structure(new GeoCountryChart(
                    $map->column('location.country')->toAxis('country', 'Country'),
                    $map->column('statistics.sessions')->toAxis('sessions', 'Sessions')
                ));
            })
            ->withoutViews();

        $module->chart('google-analytics-location-city-breakdown')
            ->fromTable('google-analytics-location-city-breakdown')
            ->map(function (ChartTableMapperDefinition $map) {
                $map->structure(new GeoCityChart(
                    $map->column('location.city')->toAxis('city', 'City'),
                    $map->column('location.city_lat_lng')->toAxis('city_lat_lng', 'City Lat/Lng'),
                    $map->column('statistics.sessions')->toAxis('sessions', 'Sessions'),
                    $this->mapCountry
                ));
            })
            ->withoutViews();

        $module->widget('google-analytics-sessions-last-month-chart')
            ->label('Last month sessions')
            ->withChart('google-analytics-sessions')
            ->matching(function (ChartCriteria $criteria) {
                $criteria->where('date', '>=', Date::fromNative(new \DateTimeImmutable())->subMonths(1), true);
            });

        $module->widget('google-analytics-location-breakdown')
            ->label('User location breakdown')
            ->withChart($this->chartMode->is(GoogleChartMode::CITY) ? 'google-analytics-location-city-breakdown' : 'google-analytics-location-country-breakdown')
            ->allData();

        $module->widget('google-analytics-browsers-breakdown')
            ->label('User browser breakdown')
            ->withChart('google-analytics-browser-breakdown')
            ->allData();
    }

    protected function mapSessionStatistics(GroupedTableDefinition $map)
    {
        $map->column(Column::name('statistics')->label('Statistics')->components([
            Field::create('sessions', 'Sessions')->int(),
            Field::create('page_views', 'Page Views')->int(),
        ]));

        $map->sum('statistics.sessions')->toComponent('statistics.sessions');
        $map->sum('statistics.page_views')->toComponent('statistics.page_views');
    }

    private function loadAnalyticsWithBreakdown(string $name, array $breakdownColumns, array $gaDimensionComponentIdMap) : ITableDisplay
    {
        $expiry     = 24 * 60 * 60;
        $dataSource = new GoogleAnalyticsTableDataSource(
            $this->client->data_ga,
            $this->viewId,
            365,
            $breakdownColumns,
            $gaDimensionComponentIdMap
        );

        $cacheItem = $this->cache->getItem(strtr($name, ['-' => '__']) . '__' . $this->viewId);

        if (!$cacheItem->isHit()) {
            $cacheItem->set($dataSource->load()->getSections()[0]->getRowArray());
            $cacheItem->expiresAfter($expiry);

            $this->cache->save($cacheItem);
        }

        return new TableDisplay($name, new ArrayTableDataSource(
            $dataSource->getStructure(),
            $cacheItem->get()
        ), []);
    }
}
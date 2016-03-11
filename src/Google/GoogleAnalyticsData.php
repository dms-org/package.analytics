<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\DateTime\Date;
use Dms\Common\Structure\Field;
use Dms\Core\Module\Definition\ModuleDefinition;
use Dms\Core\Module\Definition\Table\TableViewDefinition;
use Dms\Core\Module\Table\TableDisplay;
use Dms\Core\Table\Builder\Column;
use Dms\Core\Table\Chart\DataSource\Definition\ChartTableMapperDefinition;
use Dms\Core\Table\Chart\Structure\ChartAxis;
use Dms\Core\Table\Chart\Structure\LineChart;
use Dms\Core\Table\Chart\Structure\PieChart;
use Dms\Core\Table\DataSource\Definition\GroupedTableDefinition;
use Dms\Package\Analytics\IAnalyticsData;
use Google_Service_Analytics;

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
     * GoogleAnalyticsData constructor.
     *
     * @param Google_Service_Analytics $client
     * @param int                      $viewId
     */
    public function __construct(Google_Service_Analytics $client, int $viewId)
    {
        $this->client = $client;
        $this->viewId = $viewId;
    }

    /**
     * @inheritDoc
     */
    public function registerWidgets(ModuleDefinition $module)
    {
        $module->custom()->table(new TableDisplay(
            'google-analytics-data',
            new GoogleAnalyticsTableDataSource($this->client->data_ga, $this->viewId, 365),
            []
        ));

        $module->table('google-analytics-sessions')
            ->fromPreviousTable('google-analytics-data')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('date');

                $this->mapSessionStatistics($map);
            })
            ->withViews(function (TableViewDefinition $view) {
                $now = Date::fromNative(new \DateTimeImmutable());

                $view->name('last-week', 'Last Week')
                    ->asDefault()
                    ->where('date', '>=', $now->subWeeks(1), true);

                $view->name('last-month', 'Last Month')
                    ->where('date', '>=', $now->subMonths(1), true);

                $view->name('last-year', 'Last Year')
                    ->where('date', '>=', $now->subYears(1), true);
            });

        $module->table('google-analytics-location-country-breakdown')
            ->fromPreviousTable('google-analytics-data')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('location.country');

                $this->mapSessionStatistics($map);
            });

        $module->table('google-analytics-location-city-breakdown')
            ->fromPreviousTable('google-analytics-data')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('location.country');
                $map->groupedBy('location.city');

                $this->mapSessionStatistics($map);
            });

        $module->table('google-analytics-location-browser-breakdown')
            ->fromPreviousTable('google-analytics-data')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('browser.name');

                $this->mapSessionStatistics($map);
            });

        $module->table('google-analytics-location-browser-version-breakdown')
            ->fromPreviousTable('google-analytics-data')
            ->withStructure(function (GroupedTableDefinition $map) {
                $map->groupedBy('browser.name');
                $map->groupedBy('browser.version');

                $this->mapSessionStatistics($map);
            });

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
            });

        $module->chart('google-analytics-browser-breakdown')
            ->fromTable('google-analytics-browser-breakdown')
            ->map(function (ChartTableMapperDefinition $map) {
                $map->structure(new PieChart(
                    $map->column('browser.name')->toAxis('browser', 'Browser'),
                    $map->column('statistics.sessions')->toAxis('sessions', 'Sessions')
                ));
            });
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
}
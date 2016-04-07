<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Google;

use Dms\Common\Structure\DateTime\Date;
use Dms\Common\Structure\DateTime\Form\DateType;
use Dms\Common\Structure\Field;
use Dms\Common\Structure\Geo\Form\LatLngType;
use Dms\Common\Structure\Geo\LatLng;
use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Form\Field\Type\EnumType;
use Dms\Core\Form\IField;
use Dms\Core\Model\Criteria\Condition\ConditionOperator;
use Dms\Core\Table\Builder\Column;
use Dms\Core\Table\Data\TableRow;
use Dms\Core\Table\DataSource\TableDataSource;
use Dms\Core\Table\IRowCriteria;
use Dms\Core\Table\ITableRow;
use Dms\Core\Table\TableStructure;
use Dms\Core\Util\Debug;
use Google_Service_Analytics_DataGa_Resource;
use Google_Service_Analytics_GaData;

/**
 * The google analytics table data display
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class GoogleAnalyticsTableDataSource extends TableDataSource
{
    /**
     * @var Google_Service_Analytics_DataGa_Resource
     */
    protected $client;
    /**
     * @var int
     */
    protected $viewId;

    /**
     * @var array
     */
    protected $tableMap;

    /**
     * @var array
     */
    protected $gaMap;

    /**
     * @var int
     */
    protected $defaultDaysAgo;

    /**
     * @var array
     */
    private $gaDimensionComponentIdMap;

    /**
     * GoogleAnalyticsTableDataSource constructor.
     *
     * @param Google_Service_Analytics_DataGa_Resource $client
     * @param int                                      $viewId
     * @param int                                      $defaultDaysAgo
     * @param array                                    $breakdownColumns
     * @param array                                    $gaDimensionComponentIdMap
     */
    public function __construct(
        Google_Service_Analytics_DataGa_Resource $client,
        int $viewId,
        int $defaultDaysAgo,
        array $breakdownColumns,
        array $gaDimensionComponentIdMap
    ) {
        parent::__construct($this->tableStructure($breakdownColumns));

        $this->client                    = $client;
        $this->viewId                    = $viewId;
        $this->defaultDaysAgo            = $defaultDaysAgo;
        $this->gaDimensionComponentIdMap = $gaDimensionComponentIdMap;

        foreach ($this->tableMap() as $gaColumn => $componentId) {
            list($column, $component) = $this->structure->getColumnAndComponent($componentId);
            $componentId               = $column->getName() . '.' . $component->getName();
            $this->tableMap[$gaColumn] = $componentId;
            $this->gaMap[$componentId] = $gaColumn;
        }
    }

    protected function tableStructure(array $breakdownColumns) : TableStructure
    {
        return new TableStructure(array_merge([
            Column::name('statistics')->label('Statistics')->components([
                Field::create('sessions', 'Sessions')->int(),
                Field::create('page_views', 'Page Views')->int(),
            ]),
        ], $breakdownColumns));
    }

    protected function tableMap() : array
    {
        return $this->gaDimensionComponentIdMap + [
            'ga:sessions'  => 'statistics.sessions',
            'ga:pageviews' => 'statistics.page_views',
        ];
    }

    /**
     * Returns whether the supplied component can be used within row criteria
     *
     * @param string $componentId
     *
     * @return bool
     */
    public function canUseColumnComponentInCriteria(string $componentId) : bool
    {
        return true;
    }

    /**
     * @param IRowCriteria|null $criteria
     *
     * @return ITableRow[]
     */
    protected function loadRows(IRowCriteria $criteria = null) : array
    {
        return $this->mapDataToRows($this->loadAnalyticsDataTable($criteria));
    }

    protected function loadAnalyticsDataTable(IRowCriteria $criteria = null) : Google_Service_Analytics_GaData
    {
        $criteria = $criteria ?? $this->criteria()->loadAll();

        $this->filterOutStartAndEndDateFrom($criteria, $startDate, $endDate);

        $options = [
            'dimensions'  => implode(',', array_keys($this->gaDimensionComponentIdMap)),
            'start-index' => $criteria->getRowsToSkip() + 1,
        ];

        if ($criteria->getConditionGroups() && $filter = $this->buildFilterParam($criteria)) {
            $options['filters'] = $filter;
        }
        if ($criteria->getOrderings()) {
            $options['sort'] = $this->buildSortParam($criteria);
        }

        if ($criteria->getAmountOfRows() !== null) {
            $options['max-results'] = $criteria->getAmountOfRows();
        }

        return $this->client->get(
            'ga:' . $this->viewId,
            $startDate,
            $endDate,
            'ga:pageviews,ga:sessions',
            $options
        );
    }

    protected function filterOutStartAndEndDateFrom(IRowCriteria $criteria, &$startDate, &$endDate)
    {
        if ($criteria->getConditionMode() === IRowCriteria::CONDITION_MODE_AND) {
            foreach ($criteria->getConditionGroups() as $group) {
                if (count($group->getConditions()) === 1 || $group->getConditionMode() === IRowCriteria::CONDITION_MODE_AND) {
                    foreach ($group->getConditions() as $condition) {
                        if ($condition->getComponentId() === 'date.date') {
                            if ($condition->getOperator()->getOperator() === '>=') {
                                $startDate = $condition->getValue()->format('Y-m-d');
                            }

                            if ($condition->getOperator()->getOperator() === '<=') {
                                $endDate = $condition->getValue()->format('Y-m-d');
                            }
                        }
                    }
                }
            }
        }

        if (!$startDate) {
            $startDate = $this->defaultDaysAgo . 'daysAgo';
        }

        if (!$endDate) {
            $endDate = 'today';
        }

        return $criteria;
    }

    protected function buildFilterParam(IRowCriteria $criteria) : string
    {
        $modes       = [
            IRowCriteria::CONDITION_MODE_AND => ';',
            IRowCriteria::CONDITION_MODE_OR  => ',',
        ];
        $paramGroups = [];

        foreach ($criteria->getConditionGroups() as $group) {
            $paramGroup = [];

            foreach ($group->getConditions() as $condition) {
                if ($condition->getComponentId() === 'date.date') {
                    continue;
                }

                $paramGroup[] = $this->gaMap[$condition->getComponentId()]
                    . $this->mapOperator($condition->getOperator()->getOperator())
                    . $this->mapValue($condition->getValue());
            }

            if (!empty($paramGroup)) {
                $paramGroups[] = implode($modes[$group->getConditionMode()], $paramGroup);
            }
        }

        return implode($modes[$criteria->getConditionMode()], $paramGroups);
    }

    protected function mapOperator(string $operator) : string
    {
        $operators = [
            ConditionOperator::EQUALS                => '==',
            ConditionOperator::NOT_EQUALS            => '!=',
            ConditionOperator::GREATER_THAN          => '>',
            ConditionOperator::LESS_THAN             => '<',
            ConditionOperator::GREATER_THAN_OR_EQUAL => '>=',
            ConditionOperator::LESS_THAN_OR_EQUAL    => '<=',
            ConditionOperator::STRING_CONTAINS       => '=@',
        ];

        if (!isset($operators[$operator])) {
            throw InvalidArgumentException::format('Unsupported operator: ' . $operators);
        }

        return $operators[$operator];
    }

    protected function mapValue($value) : string
    {
        if (is_scalar($value) || $value === null) {
            return (string)$value;
        }

        if ($value instanceof Date) {
            return $value->format('Y-m-d');
        }

        throw InvalidArgumentException::format('Unknown value type: ' . Debug::getType($value));
    }

    protected function buildSortParam(IRowCriteria $criteria) : string
    {
        $sortParams = [];

        foreach ($criteria->getOrderings() as $ordering) {
            $sortParams[] = ($ordering->isAsc() ? '' : '-') . $this->gaMap[$ordering->getComponentId()];
        }

        return implode(',', $sortParams);
    }

    protected function mapDataToRows(Google_Service_Analytics_GaData $data) : array
    {
        $rows = [];

        /** @var IField[] $columnIndexFieldMap */
        $columnComponentIdMap = [];
        $columnIndexFieldMap  = [];
        $gaIndexMap           = [];

        foreach ($data->getColumnHeaders() as $key => $column) {
            $gaIndexMap[$column->getName()] = $key;
            $columnComponentIdMap[$key] = explode('.', $this->tableMap[$column->getName()]);
            $columnIndexFieldMap[$key]  = $this->structure->getComponent($this->tableMap[$column->getName()])
                ->getType()
                ->getOperator(ConditionOperator::EQUALS)
                ->getField();
        }

        foreach ($data->getRows() ?: [] as $row) {
            $processedRow = [];

            foreach ($row as $key => $value) {
                list($column, $component) = $columnComponentIdMap[$key];
                $processedRow[$column][$component] = $this->transformValue($columnIndexFieldMap[$key], $value, $row, $gaIndexMap);
            }

            $rows[] = new TableRow($processedRow);
        }

        return $rows;
    }

    protected function transformValue(IField $field, string $value, array $row, array $gaIndexMap)
    {
        if ($field->getType() instanceof LatLngType) {
            return new LatLng((float)$row[$gaIndexMap['ga:latitude']], (float)$row[$gaIndexMap['ga:longitude']]);
        } elseif ($field->getType() instanceof DateType) {
            return Date::fromFormat('Ymd', $value);
        } elseif ($field->getType() instanceof EnumType) {
            $class = $field->getType()->get(EnumType::ATTR_ENUM_CLASS);

            return new $class($value);
        } else {
            return $field->process($value);
        }
    }

    /**
     * @param IRowCriteria|null $criteria
     *
     * @return int
     */
    protected function loadCount(IRowCriteria $criteria = null) : int
    {
        $criteria = $criteria ? $criteria->asNewCriteria() : $this->criteria()->loadAll();

        return (int)$this->loadAnalyticsDataTable($criteria->maxRows(0))['totalResults'];
    }
}
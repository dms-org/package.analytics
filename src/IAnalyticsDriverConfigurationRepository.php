<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Core\Model\ICriteria;
use Dms\Core\Model\ISpecification;
use Dms\Core\Persistence\IRepository;

/**
 * The analytics driver configuration repository interface.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IAnalyticsDriverConfigurationRepository extends IRepository
{
    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration[]
     */
    public function getAll() : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration
     */
    public function get($id);

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration[]
     */
    public function getAllById(array $ids) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration|null
     */
    public function tryGet($id);

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration[]
     */
    public function tryGetAll(array $ids) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration[]
     */
    public function matching(ICriteria $criteria) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfiguration[]
     */
    public function satisfying(ISpecification $specification) : array;
}
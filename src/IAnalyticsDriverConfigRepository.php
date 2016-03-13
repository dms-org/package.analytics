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
interface IAnalyticsDriverConfigRepository extends IRepository
{
    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig[]
     */
    public function getAll() : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig
     */
    public function get($id);

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig[]
     */
    public function getAllById(array $ids) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig|null
     */
    public function tryGet($id);

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig[]
     */
    public function tryGetAll(array $ids) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig[]
     */
    public function matching(ICriteria $criteria) : array;

    /**
     * {@inheritDoc}
     *
     * @return AnalyticsDriverConfig[]
     */
    public function satisfying(ISpecification $specification) : array;
}
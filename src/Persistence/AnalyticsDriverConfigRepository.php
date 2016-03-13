<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Persistence;

use Dms\Core\Persistence\Db\Connection\IConnection;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\DbRepository;
use Dms\Package\Analytics\AnalyticsDriverConfig;
use Dms\Package\Analytics\IAnalyticsDriverConfigRepository;

/**
 * The analytics driver configuration repository
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverConfigRepository extends DbRepository implements IAnalyticsDriverConfigRepository
{
    /**
     * @inheritDoc
     */
    public function __construct(IConnection $connection, IOrm $orm)
    {
        parent::__construct($connection, $orm->getEntityMapper(AnalyticsDriverConfig::class));
    }
}
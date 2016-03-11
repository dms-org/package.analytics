<?php declare(strict_types = 1);

namespace Dms\Package\Analytics\Persistence;

use Dms\Core\Persistence\Db\Connection\IConnection;
use Dms\Core\Persistence\Db\Mapping\IOrm;
use Dms\Core\Persistence\DbRepository;
use Dms\Package\Analytics\AnalyticsDriverConfiguration;
use Dms\Package\Analytics\IAnalyticsDriverConfigurationRepository;

/**
 * The analytics driver configuration repository
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverConfigurationRepository extends DbRepository implements IAnalyticsDriverConfigurationRepository
{
    /**
     * @inheritDoc
     */
    public function __construct(IConnection $connection, IOrm $orm)
    {
        parent::__construct($connection, $orm->getEntityMapper(AnalyticsDriverConfiguration::class));
    }
}
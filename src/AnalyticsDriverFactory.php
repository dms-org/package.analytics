<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Util\Debug;
use Dms\Package\Analytics\Google\GoogleAnalyticsDriver;
use Interop\Container\ContainerInterface;

/**
 * The analytics driver factory.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverFactory
{
    private static $drivers = [
        'google' => GoogleAnalyticsDriver::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $iocContainer;

    /**
     * @var IAnalyticsDriver[]
     */
    private $driversCache = [];

    /**
     * AnalyticsDriverFactory constructor.
     *
     * @param ContainerInterface $iocContainer
     */
    public function __construct(ContainerInterface $iocContainer)
    {
        $this->iocContainer = $iocContainer;
    }

    /**
     * @return IAnalyticsDriver[]
     */
    public function getDrivers() : array
    {
        foreach (self::$drivers as $name => $driverClass) {
            if (!isset($this->driversCache[$name])) {
                $this->load($name);
            }
        }

        return $this->driversCache;
    }

    /**
     * @return string[]
     */
    public function getDriverOptions() : array
    {
        $options = [];

        foreach ($this->getDrivers() as $driver) {
            $options[$driver->getName()] = $driver->getLabel();
        }

        return $options;
    }

    /**
     * @param string $name
     * @param string $class
     */
    public function registerDriver(string $name, string $class)
    {
        InvalidArgumentException::verify(
            is_a($class, IAnalyticsDriver::class, true),
            'Class must implement %s, %s given',
            IAnalyticsDriver::class, $class
        );

        self::$drivers[$name] = $class;
    }

    /**
     * @param string $driverName
     *
     * @return IAnalyticsDriver
     * @throws InvalidArgumentException
     */
    public function load(string $driverName) : IAnalyticsDriver
    {
        if (!isset(self::$drivers[$driverName])) {
            throw InvalidArgumentException::format(
                'Invalid driver name supplied to %s: expecting one of (%s), \'%s\' given',
                __METHOD__, Debug::formatValues(array_keys(self::$drivers)), $driverName
            );
        }

        if (!isset($this->driversCache[$driverName])) {
            $this->driversCache[$driverName] = $this->iocContainer->get(self::$drivers[$driverName]);
        }

        return $this->driversCache[$driverName];
    }
}
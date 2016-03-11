<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Core\Exception\InvalidArgumentException;
use Dms\Core\Util\Debug;
use Dms\Package\Analytics\Google\GoogleAnalyticsDriver;

/**
 * The analytics driver factory.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsDriverFactory
{
    /**
     * @var IAnalyticsDriver[]
     */
    private static $drivers;

    /**
     * @return IAnalyticsDriver[]
     */
    public static function getDrivers() : array
    {
        if (self::$drivers === null) {
            self::$drivers = [
                'google' => new GoogleAnalyticsDriver(),
            ];
        }

        return self::$drivers;
    }

    /**
     * @return string[]
     */
    public static function getDriverOptions() : array
    {
        $options = [];

        foreach (self::getDrivers() as $driver) {
            $options[$driver->getName()] = $driver->getLabel();
        }

        return $options;
    }

    /**
     * @param IAnalyticsDriver $driver
     *
     * @return void
     */
    public static function registerDriver(IAnalyticsDriver $driver)
    {
        self::getDrivers();

        self::$drivers[$driver->getName()] = $driver;
    }

    /**
     * @param string $driverName
     *
     * @return IAnalyticsDriver
     * @throws InvalidArgumentException
     */
    public static function load(string $driverName) : IAnalyticsDriver
    {
        $drivers = self::getDrivers();
        if (!isset($drivers[$driverName])) {
            throw InvalidArgumentException::format(
                'Invalid driver name supplied to %s: expecting one of (%s), \'%s\' given',
                __METHOD__, Debug::formatValues(array_keys($drivers)), $driverName
            );
        }

        return $drivers[$driverName];
    }
}
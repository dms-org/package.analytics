<?php

namespace Dms\Package\Analytics;

/**
 * The analytics embed code service.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
class AnalyticsEmbedCodeService
{
    /**
     * @var IAnalyticsDriverConfigRepository
     */
    protected $analyticsRepository;

    /**
     * @var AnalyticsDriverFactory
     */
    protected $driverFactory;

    /**
     * AnalyticsEmbedCodeService constructor.
     *
     * @param IAnalyticsDriverConfigRepository $analyticsRepository
     * @param AnalyticsDriverFactory           $driverFactory
     */
    public function __construct(IAnalyticsDriverConfigRepository $analyticsRepository, AnalyticsDriverFactory $driverFactory)
    {
        $this->analyticsRepository = $analyticsRepository;
        $this->driverFactory = $driverFactory;
    }

    /**
     * @return string
     */
    public function getEmbedCode() : string
    {
        $embedCodes = [];

        foreach ($this->analyticsRepository->getAll() as $driverConfig) {
            $embedCodes[] = $this->driverFactory->load($driverConfig->driverName)
                ->getEmbedCode($driverConfig->options);
        }

        return implode('', $embedCodes);
    }
}
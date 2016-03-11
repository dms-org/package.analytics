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
     * @var IAnalyticsDriverConfigurationRepository
     */
    protected $analyticsRepository;

    /**
     * AnalyticsEmbedCodeService constructor.
     *
     * @param IAnalyticsDriverConfigurationRepository $analyticsRepository
     */
    public function __construct(IAnalyticsDriverConfigurationRepository $analyticsRepository)
    {
        $this->analyticsRepository = $analyticsRepository;
    }

    /**
     * @return string
     */
    public function getEmbedCode() : string
    {
        $embedCodes = [];

        foreach ($this->analyticsRepository->getAll() as $driverConfig) {
            $embedCodes[] = $driverConfig->generateEmbedCode();
        }

        return implode('', $embedCodes);
    }
}
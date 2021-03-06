<?php declare(strict_types = 1);

namespace Dms\Package\Analytics;

use Dms\Common\Structure\Web\Html;
use Dms\Core\Form\Object\FormObject;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The analytics driver interface.
 *
 * @author Elliot Levin <elliotlevin@hotmail.com>
 */
interface IAnalyticsDriver
{
    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName() : string;

    /**
     * Gets the label.
     *
     * @return string
     */
    public function getLabel() : string;

    /**
     * Gets the installation instructions.
     *
     * @return Html
     */
    public function getInstallationInstructions() : Html;

    /**
     * Gets the cache.
     *
     * @return CacheItemPoolInterface
     */
    public function getCache() : CacheItemPoolInterface;

    /**
     * Sets the cache.
     *
     * @param CacheItemPoolInterface $cache
     *
     * @return void
     */
    public function setCache(CacheItemPoolInterface $cache);

    /**
     * Gets the options form for the providing the required data
     * to connect with the analytics API.
     *
     * @return FormObject
     */
    public function getOptionsForm() : FormObject;

    /**
     * Validates the supplied credentials for the analytics API
     * are correct.
     *
     * @param FormObject $options
     *
     * @return bool
     */
    public function validate(FormObject $options) : bool;

    /**
     * Gets the available analytics data sources for the supplied
     * form data.
     *
     * @param FormObject $options
     *
     * @return IAnalyticsData
     */
    public function getAnalyticsData(FormObject $options) : IAnalyticsData;

    /**
     * Gets the embed code to be displayed on the frontend of the website.
     *
     * @param FormObject $options
     *
     * @return string
     */
    public function getEmbedCode(FormObject $options) : string;
}
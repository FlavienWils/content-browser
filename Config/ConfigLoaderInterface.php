<?php

namespace Netgen\Bundle\ContentBrowserBundle\Config;

interface ConfigLoaderInterface
{
    /**
     * Loads the configuration by its name.
     *
     * @param string $configName
     *
     * @throws \Netgen\Bundle\ContentBrowserBundle\Exceptions\InvalidArgumentException If config could not be found
     *
     * @return \Netgen\Bundle\ContentBrowserBundle\Config\ConfigurationInterface
     */
    public function loadConfig($configName);
}

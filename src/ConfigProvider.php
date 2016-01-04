<?php

namespace Fozzy\Di;

use Exception;

/**
 * Class ConfigProvider
 *
 * This provider pulls the service definitions from the config service,
 * and registers the definitions with the service container.
 *
 * @package Fozzy\Di
 */
class ConfigProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     * @return mixed|void
     */
    public function register(ContainerInterface $container)
    {
        try {
            $config = $container->get('di.config');
            $container->setDefinitions($config);
        } catch (Exception $e){
            // do nothing - nothing to register
        }

    }
}

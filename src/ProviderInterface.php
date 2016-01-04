<?php

namespace Fozzy\Di;

/**
 * Interface ProviderInterface
 *
 * @package Fozzy\Di
 */
interface ProviderInterface
{
    /**
     * Register services from this provider with the parent container.
     *
     * @param ContainerInterface $container
     * @return mixed
     */
    public function register(ContainerInterface $container);
}

<?php

namespace Fozzy\Di;

use RuntimeException;

/**
 * Class ContainerAwareTrait
 *
 * @package Fozzy\Di
 */
trait ContainerAwareTrait
{
    /**
     * @var null|Container
     */
    protected $container = null;

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @throws RuntimeException
     * @return Container|null
     */
    public function getContainer()
    {
        if (null === $this->container) {
            throw new RuntimeException("No container has been set.");
        }

        return $this->container;
    }
}

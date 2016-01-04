<?php

namespace Fozzy\Di;

/**
 * Interface ContainerInterface
 *
 * @package Fozzy\Di
 */
interface ContainerInterface
{
    /**
     * @param string $id
     * @return mixed
     */
    public function get($id);

    /**
     * @param string $id
     * @param mixed  $service
     * @return mixed
     */
    public function set($id, $service);

    /**
     * @param string           $id
     * @param array|Definition $definition
     * @return mixed
     */
    public function setDefinition($id, $definition);

    /**
     * @param array $definitions
     * @return mixed
     */
    public function setDefinitions(array $definitions);
}

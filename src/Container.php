<?php

namespace Fozzy\Di;

use ArrayAccess;
use ReflectionClass;

/**
 * Class Container
 *
 * @package Fozzy\Di
 */
class Container implements ArrayAccess, ContainerInterface
{
    /**
     * @var array
     */
    protected $services = [];

    /**
     * @var array
     */
    protected $definitions = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->set('container', $this);
    }

    /**
     * @param  string $id
     * @return mixed
     */
    public function get($id)
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        if (isset($this->definitions[$id])) {
            return $this->buildService($id);
        }

        throw new Exception\InvalidArgumentException(sprintf('Service "%s" does not exist.', $id));
    }

    /**
     * Does a service exist?
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        foreach ([
            $this->services,
            $this->definitions
        ] as $loc) {
            if (isset($loc[$id])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sets a service within this container.
     *
     * @param string $id
     * @param mixed  $service
     * @return $this
     */
    public function set($id, $service)
    {
        $this->services[$id] = $service;

        return $this;
    }

    /**
     * Register a provider with this container.
     *
     * @param ProviderInterface $provider
     * @return $this
     */
    public function register($provider)
    {
        if (is_string($provider)) {
            $provider = new $provider();
        }

        if (!$provider instanceof ProviderInterface) {
            throw new Exception\InvalidArgumentException('Provider must implement ProviderInterface.');
        }

        $provider->register($this);

        return $this;
    }

    /**
     * @param array $definitions
     * @return $this
     */
    public function setDefinitions(array $definitions = [])
    {
        $intersection = array_intersect(
            array_keys($definitions),
            array_keys($this->definitions)
        );

        if (count($intersection) > 0) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The following definitions already existed within the Container: %s',
                implode(', ', $intersection)
            ));
        }

        $this->definitions = $this->definitions + $definitions;

        return $this;
    }

    /**
     * @param string|array        $id
     * @param Definition|array    $definition
     * @return $this
     */
    public function setDefinition($id, $definition)
    {
        if (isset($this->definitions[$id])) {
            throw new Exception\InvalidArgumentException(sprintf('A definition for %s already exists', $id));
        }

        $this->definitions[$id] = $definition;

        return $this;
    }

    /**
     * Builds a Service from an existing definition.
     *
     * @param  string $id
     * @return mixed|object
     * @throws Exception\InvalidDefinitionException
     */
    protected function buildService($id)
    {
        if (!isset($this->definitions[$id])) {
            throw new Exception\InvalidArgumentException(sprintf('Invalid service definition "%s"', $id));
        }

        $definition = $this->definitions[$id];

        if (!$definition instanceof Definition) {
            $definition = new Definition($definition);
        }

        if ($factory = ($definition->getFactory())) {
            // Factories
            $service = call_user_func_array(
                $factory,
                $this->resolveArguments(
                    $definition->getArguments(),
                    $id
                )
            );
        } else if ($class = ($definition->getClass())) {
            // Standard class/invokable
            if ($definition->hasArguments()) {

                $reflected = new ReflectionClass($class);
                $service   = $reflected->newInstanceArgs(
                    $this->resolveArguments(
                        $definition->getArguments(),
                        $id
                    )
                );
            } else {
                // Standard class but with no arguments - there could still be method calls however
                $service = new $class();
            }
        } else {
            throw new Exception\InvalidDefinitionException(
                sprintf('No factory or class is specified for definition of "%s".', $id)
            );
        }

        // Now call any methods we may need to
        foreach ($definition->getMethodCalls() as $method => $arguments) {
            call_user_func_array(
                [$service, $method],
                $this->resolveArguments($arguments, $id)
            );
        }

        // Share if we should
        if ($definition->isShared()) {
            $this->services[$id] = $service;
        }

        return $service;
    }

    /**
     * Resolves multiple argument references
     *
     * @param  array         $arguments
     * @param  null|string   $id            Service ID
     * @throws Exception\InvalidReferenceException
     * @return mixed
     */
    public function resolveArguments($arguments, $id = null)
    {
        foreach ($arguments as &$argument) {
            $argument = $this->resolveArgument($argument, $id);
        }

        return $arguments;
    }

    /**
     * Resolves the following types of arguments:
     *
     *  Classes
     *      - \HL\Util\Cache            = new \HL\Util\Cache
     *
     *  Service ID Requested
     *      - #id                       = Service id attempting to be generated
     *
     *  Internal Service References
     *       - @config                  = $service->get('config')
     *       - @events                  = $service->get('events')
     *       - @config/cache/adapter    = $service->get('config')['cache']['adapter']
     *
     *
     * @param mixed  $argument
     * @param string $id            Service ID
     * @throws Exception\InvalidReferenceException
     * @return mixed|null|void
     */
    public function resolveArgument($argument, $id = null)
    {
        if (is_string($argument)) {

            // #id will pass the id of the service being requested.
            // This allows the same factory to generate multiple services
            if ('#id' === substr($argument, 0, 3)) {
                return $id;
            }

            // Internal Service Reference
            // @config
            // @config/cache/adapter = $service->get('config')['cache']['adapter'] || null
            if ('@' === substr($argument, 0, 1)) {
                $parts = null;
                $name  = substr($argument, 1);

                if (false !== strpos($name, '/')) {
                    $parts = explode('/', $name);
                    $name  = array_shift($parts);
                }

                if (!$this->has($name)) {
                    throw new Exception\InvalidReferenceException(
                        sprintf('Not possible to resolve argument "%s", service does not exist.', $argument)
                    );
                }

                $argument = $this->get($name);

                // Allow array sub-access of services that might be an array
                if (null !== $parts
                    && (is_array($argument) || $argument instanceof \ArrayAccess)
                ) {
                    foreach ($parts as $sub) {
                        if (isset($argument[$sub])) {
                            $argument = $argument[$sub];
                        } else {
                            $argument = null;
                            break;
                        }
                    }
                }
            } else if ('\\' ===  substr($argument, 0, 1)) {

                // Direct class references - include the namespace (even if global:  \Exception)
                $argument = new $argument();
            }
        }

        return $argument;
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset An offset to check for.
     * @return boolean      true on success or false on failure.
     *                      The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset The offset to retrieve.
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset The offset to unset.
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->services[$offset]);
    }
}

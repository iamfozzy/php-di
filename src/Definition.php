<?php

namespace Fozzy\Di;

/**
 * Class Definition
 *
 * @package Fozzy\Di
 */
class Definition
{
    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $defaultArguments = [];

    /**
     * @var string
     */
    protected $factory = null;

    /**
     * @var string
     */
    protected $class = null;

    /**
     * @var array       Method calls
     */
    protected $calls = [];

    /**
     * @var bool        Is this a shared definition? ie - should a new instance be returned each time or not?
     */
    protected $shared = true;

    /**
     * @param array $config
     */
    public function __construct($config = [])
    {
        if ($config instanceof \Closure) {
            $this->factory = $config;
        }

        if (isset($config['factory'])) {
            $this->factory = $config['factory'];
        }

        if (isset($config['class'])) {
            $this->class = $config['class'];
        }

        if (isset($config['arguments'])) {
            $this->arguments = $config['arguments'];
        }

        if (isset($config['method_calls'])) {
            $this->calls = $config['method_calls'];
        }

        if (isset($config['shared'])) {
            $this->shared = (bool) $config['shared'];
        }
    }

    /**
     * Sets the methods to call after service initialization.
     *
     * @param array $calls An array of method calls
     *
     * @return Definition The current instance
     *
     * @api
     */
    public function setMethodCalls(array $calls = array())
    {
        $this->calls = array();
        foreach ($calls as $call) {
            $this->addMethodCall($call[0], $call[1]);
        }

        return $this;
    }

    /**
     * Adds a method to call after service initialization.
     *
     * @param string $method    The method name to call
     * @param array  $arguments An array of arguments to pass to the method call
     *
     * @return Definition The current instance
     *
     * @throws Exception\InvalidArgumentException on empty $method param
     *
     * @api
     */
    public function addMethodCall($method, array $arguments = array())
    {
        if (empty($method)) {
            throw new Exception\InvalidArgumentException(sprintf('Method name cannot be empty.'));
        }

        $this->calls[] = array($method, $arguments);

        return $this;
    }

    /**
     * Removes a method to call after service initialization.
     *
     * @param string $method The method name to remove
     *
     * @return Definition The current instance
     *
     * @api
     */
    public function removeMethodCall($method)
    {
        foreach ($this->calls as $i => $call) {
            if ($call[0] === $method) {
                unset($this->calls[$i]);
                break;
            }
        }

        return $this;
    }

    /**
     * Check if the current definition has a given method to call after service initialization.
     *
     * @param string $method The method name to search for
     *
     * @return bool
     *
     * @api
     */
    public function hasMethodCall($method)
    {
        foreach ($this->calls as $call) {
            if ($call[0] === $method) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getMethodCalls()
    {
        return $this->calls;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return !empty($this->arguments) ? $this->arguments : $this->defaultArguments;
    }

    /**
     * @param array $arguments
     * @return self
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasArguments()
    {
        return count($this->arguments) > 0;
    }

    /**
     * @param  mixed $argument
     * @return $this
     */
    public function addArgument($argument)
    {
        $this->arguments[] = $argument;

        return $this;
    }

    /**
     * @param int   $index
     * @param mixed $argument
     * @return $this
     */
    public function replaceArgument($index, $argument)
    {
        if ($index < 0 || $index > count($this->arguments) - 1) {
            throw new Exception\OutOfBoundsException(
                sprintf('The index "$d" is not in the range [0, %d].', $index, count($this->arguments) - 1)
            );
        }

        $this->arguments[$index] = $argument;

        return $this;
    }

    /**
     * @param  string $index
     * @return mixed
     */
    public function getArgument($index)
    {
        if ($index < 0 || $index > count($this->arguments) - 1) {
            throw new Exception\OutOfBoundsException(
                sprintf('The index "%d" is not in the range [0, %d].', $index, count($this->arguments) - 1)
            );
        }

        return $this->arguments[$index];
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     * @return self
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param string $factory
     * @return self
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * @return bool
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * Set the shared state of this definition.
     *
     * @param bool $isShared
     * @return $this
     */
    public function setShared($isShared = true)
    {
        $this->shared = (bool) $isShared;

        return $this;
    }
}

<?php

namespace Mathepa\Test;

/**
 * Calls a protected/private method of a class.
 *
 * @param object &$object Instantiated object that we will run method on
 * @param string $methodName Method name to call
 * @param array  $parameters Array of parameters to pass into method.
 * @return mixed Method return.
 */
function invokeMethod(&$object, $methodName, array $parameters = array())
{
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}

/**
 *
 * Applies a user supplied function to every member of an array
 *
 * @throws \InvalidArgumentFunction
 * @param array $array
 * @param callable $function
 * @return void
 */
function onEachItem(array $array, $function): void
{
    if (!is_callable($function)) {
        throw new \InvalidArgumentException(
            'Parameter "$function" is not callable'
        );
    }
    foreach ($array as $item) {
        $function($item);
    }
}

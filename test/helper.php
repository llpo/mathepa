<?php

// Helpers
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

/**
 * Calls a protected/private method of a class.
 *
 * @param object &$object Instantiated object that we will run method on
 * @param string $methodName Method name to call
 * @param array  $parameters Array of parameters to pass into method.
 * @return mixed
 */
function invokeMethod(&$object, $methodName, array $parameters = [])
{
    $reflection = new \ReflectionClass(get_class($object));
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $parameters);
}

/**
 * @param string $var
 * @return void
 */
function pr(...$var): void
{
    $cloner = new VarCloner();
    $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();
    $dumper->dump($cloner->cloneVar($var));
}

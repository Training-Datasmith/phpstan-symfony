<?php

declare(strict_types=1);

namespace PHPStan\Type\Symfony\Extension\WithConfigurationWithConstructorOptionalParams;

use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function __construct($foo = null)
    {
    }

    public function getConfigTreeBuilder()
    {
    }
}

<?php

declare(strict_types=1);

namespace PHPStan\Type\Symfony\Extension\WithConfiguration;

use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
    }
}

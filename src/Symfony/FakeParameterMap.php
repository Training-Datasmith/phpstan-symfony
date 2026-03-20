<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
final class Fake_Parameter_Map implements Parameter_Map
{
    /**
     * @return ParameterDefinition[]
     */
    public function get_parameters(): array
    {
        return [];
    }
    public function get_parameter(string $key): ?Parameter_Definition
    {
        return null;
    }
    public static function get_parameter_keys_from_node(Expr $node, Scope $scope): array
    {
        return [];
    }
}
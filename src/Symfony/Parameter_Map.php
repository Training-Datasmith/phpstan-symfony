<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
/**
 * @api
 */
interface Parameter_Map
{
    /**
     * @return ParameterDefinition[]
     */
    public function get_parameters(): array;
    public function get_parameter(string $key): ?Parameter_Definition;
    /**
     * @return array<string>
     */
    public static function get_parameter_keys_from_node(Expr $node, Scope $scope): array;
}
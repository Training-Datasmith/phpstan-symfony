<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function array_map;
use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Type;
final class Default_Parameter_Map implements Parameter_Map
{
    /** @var ParameterDefinition[] */
    private array $parameters;
    /**
     * @param ParameterDefinition[] $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }
    /**
     * @return ParameterDefinition[]
     */
    public function get_parameters(): array
    {
        return $this->parameters;
    }
    public function get_parameter(string $key): ?Parameter_Definition
    {
        return $this->parameters[$key] ?? null;
    }
    public static function get_parameter_keys_from_node(Expr $node, Scope $scope): array
    {
        $strings = $scope->get_type($node)->get_constant_strings();
        return array_map(static fn(Type $type): string => $type->get_value(), $strings);
    }
}
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
     * Returns all parameter definitions from the compiled Symfony container.
     *
     * @return Parameter_Definition[] All registered container parameters
     */
    public function get_parameters(): array;

    /**
     * Looks up a parameter definition by its container parameter key.
     *
     * @param string $key The Symfony parameter key (e.g., 'kernel.environment', 'app.feature_x')
     *
     * @return Parameter_Definition|null The parameter definition, or null if not registered
     */
    public function get_parameter(string $key): ?Parameter_Definition;

    /**
     * Extracts parameter key strings from a getParameter() call argument node.
     *
     * Attempts to statically resolve the argument to one or more string literals.
     * Returns an empty array if the argument is dynamic or cannot be determined.
     *
     * @param Expr  $node  The AST expression node passed as the parameter key argument
     * @param Scope $scope The current analysis scope for type resolution
     *
     * @return array<string> Zero or more resolved parameter key strings
     */
    public static function get_parameter_keys_from_node(Expr $node, Scope $scope): array;
}
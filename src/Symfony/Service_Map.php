<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
/**
 * @api
 */
interface Service_Map
{
    /**
     * Returns all service definitions known to the container.
     *
     * @return Service_Definition[] All registered service definitions from the compiled container XML
     */
    public function get_services(): array;

    /**
     * Looks up a service definition by its container service ID.
     *
     * @param string $id The Symfony service ID (e.g., 'App\Service\MyService' or 'my.service')
     *
     * @return Service_Definition|null The service definition, or null if not registered
     */
    public function get_service(string $id): ?Service_Definition;

    /**
     * Extracts the service ID string from a container get() call argument node.
     *
     * Attempts to statically resolve the first argument of a ContainerInterface::get() call
     * to a string literal. Returns null if the argument is dynamic or cannot be resolved.
     *
     * @param Expr  $node  The AST expression node passed as the service ID argument
     * @param Scope $scope The current analysis scope for type resolution
     *
     * @return string|null The resolved service ID string, or null if it cannot be determined
     */
    public static function get_service_id_from_node(Expr $node, Scope $scope): ?string;
}
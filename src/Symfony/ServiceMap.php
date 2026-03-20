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
     * @return ServiceDefinition[]
     */
    public function get_services(): array;
    public function get_service(string $id): ?Service_Definition;
    public static function get_service_id_from_node(Expr $node, Scope $scope): ?string;
}
<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
final class Fake_Service_Map implements Service_Map
{
    /**
     * @return ServiceDefinition[]
     */
    public function get_services(): array
    {
        return [];
    }
    public function get_service(string $id): ?Service_Definition
    {
        return null;
    }
    public static function get_service_id_from_node(Expr $node, Scope $scope): ?string
    {
        return null;
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function count;
use Php_Parser\Node\Expr;
use Php_Stan\Analyser\Scope;
final class Default_Service_Map implements Service_Map
{
    /** @var ServiceDefinition[] */
    private array $services;
    /**
     * @param ServiceDefinition[] $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }
    /**
     * @return ServiceDefinition[]
     */
    public function get_services(): array
    {
        return $this->services;
    }
    public function get_service(string $id): ?Service_Definition
    {
        return $this->services[$id] ?? null;
    }
    public static function get_service_id_from_node(Expr $node, Scope $scope): ?string
    {
        $strings = $scope->get_type($node)->get_constant_strings();
        return count($strings) === 1 ? $strings[0]->get_value() : null;
    }
}
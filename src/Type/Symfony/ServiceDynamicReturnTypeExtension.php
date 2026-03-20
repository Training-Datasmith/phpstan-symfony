<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function class_exists;
use function in_array;
use function is_string;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Symfony\Parameter_Map;
use Php_Stan\Symfony\Service_Definition;
use Php_Stan\Symfony\Service_Map;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Symfony\Component\Dependency_Injection\Parameter_Bag\Parameter_Bag;
final class Service_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    /** @var class-string */
    private string $class_name;
    private bool $constant_hassers;
    private Service_Map $service_map;
    private Parameter_Map $parameter_map;
    private ?Parameter_Bag $parameter_bag = null;
    /**
     * @param class-string $className
     */
    public function __construct(string $class_name, bool $constant_hassers, Service_Map $symfony_service_map, Parameter_Map $symfony_parameter_map)
    {
        $this->class_name = $class_name;
        $this->constant_hassers = $constant_hassers;
        $this->service_map = $symfony_service_map;
        $this->parameter_map = $symfony_parameter_map;
    }
    public function get_class(): string
    {
        return $this->class_name;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return in_array($method_reflection->get_name(), ['get', 'has'], true);
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        switch ($method_reflection->get_name()) {
            case 'get':
                return $this->get_get_type_from_method_call($method_call, $scope);
            case 'has':
                return $this->get_has_type_from_method_call($method_call, $scope);
        }
        throw new Should_Not_Happen_Exception();
    }
    private function get_get_type_from_method_call(Method_Call $method_call, Scope $scope): ?Type
    {
        if (!isset($method_call->get_args()[0])) {
            return null;
        }
        $parameter_bag = $this->try_get_parameter_bag();
        if ($parameter_bag === null) {
            return null;
        }
        $service_id = $this->service_map::get_service_id_from_node($method_call->get_args()[0]->value, $scope);
        if ($service_id !== null) {
            $service = $this->service_map->get_service($service_id);
            if ($service !== null && (!$service->is_synthetic() || $service->get_class() !== null)) {
                return new Object_Type($this->determine_service_class($parameter_bag, $service) ?? $service_id);
            }
        }
        return null;
    }
    private function try_get_parameter_bag(): ?Parameter_Bag
    {
        if ($this->parameter_bag !== null) {
            return $this->parameter_bag;
        }
        return $this->parameter_bag = $this->try_create_parameter_bag();
    }
    private function try_create_parameter_bag(): ?Parameter_Bag
    {
        if (!class_exists(Parameter_Bag::class)) {
            return null;
        }
        $parameters = [];
        foreach ($this->parameter_map->get_parameters() as $parameter_definition) {
            $parameters[$parameter_definition->get_key()] = $parameter_definition->get_value();
        }
        return new Parameter_Bag($parameters);
    }
    private function get_has_type_from_method_call(Method_Call $method_call, Scope $scope): ?Type
    {
        if (!isset($method_call->get_args()[0]) || !$this->constant_hassers) {
            return null;
        }
        $service_id = $this->service_map::get_service_id_from_node($method_call->get_args()[0]->value, $scope);
        if ($service_id !== null) {
            $service = $this->service_map->get_service($service_id);
            return new Constant_Boolean_Type($service !== null && $service->is_public());
        }
        return null;
    }
    private function determine_service_class(Parameter_Bag $parameter_bag, Service_Definition $service): ?string
    {
        $class = $service->get_class();
        if ($class === null) {
            return null;
        }
        $value = $parameter_bag->resolve_value($class);
        if (!is_string($value)) {
            return null;
        }
        return $value;
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Php_Doc\Type_String_Resolver;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Symfony\Parameter_Map;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Constant\Constant_Array_Type;
use Php_Stan\Type\Constant\Constant_Array_Type_Builder;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Generalize_Precision;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Type_Traverser;
use Php_Stan\Type\Union_Type;
use function preg_match;
use function strlen;
use Symfony\Component\Dependency_Injection\Env_Var_Processor;
final class Parameter_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    /** @var class-string */
    private string $class_name;
    private ?string $method_get = null;
    private ?string $method_has = null;
    private bool $constant_hassers;
    private Parameter_Map $parameter_map;
    private Type_String_Resolver $type_string_resolver;
    /**
     * @param class-string $className
     */
    public function __construct(string $class_name, ?string $method_get, ?string $method_has, bool $constant_hassers, Parameter_Map $symfony_parameter_map, Type_String_Resolver $type_string_resolver)
    {
        $this->class_name = $class_name;
        $this->method_get = $method_get;
        $this->method_has = $method_has;
        $this->constant_hassers = $constant_hassers;
        $this->parameter_map = $symfony_parameter_map;
        $this->type_string_resolver = $type_string_resolver;
    }
    public function get_class(): string
    {
        return $this->class_name;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        $methods = array_filter([$this->method_get, $this->method_has], static fn(?string $method): bool => $method !== null);
        return in_array($method_reflection->get_name(), $methods, true);
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        switch ($method_reflection->get_name()) {
            case $this->method_get:
                return $this->get_get_type_from_method_call($method_call, $scope);
            case $this->method_has:
                return $this->get_has_type_from_method_call($method_call, $scope);
        }
        throw new Should_Not_Happen_Exception();
    }
    private function get_get_type_from_method_call(Method_Call $method_call, Scope $scope): Type
    {
        // We don't use the method's return type because this won't work properly with lowest and
        // highest versions of Symfony ("mixed" for lowest, "array|bool|float|integer|string|null" for highest).
        $default_return_type = new Union_Type([new Array_Type(new Mixed_Type(), new Mixed_Type()), new Boolean_Type(), new Float_Type(), new Integer_Type(), new String_Type(), new Null_Type()]);
        if (!isset($method_call->get_args()[0])) {
            return $default_return_type;
        }
        $parameter_keys = $this->parameter_map::get_parameter_keys_from_node($method_call->get_args()[0]->value, $scope);
        if ($parameter_keys === []) {
            return $default_return_type;
        }
        $return_types = [];
        foreach ($parameter_keys as $parameter_key) {
            $parameter = $this->parameter_map->get_parameter($parameter_key);
            if ($parameter === null) {
                return $default_return_type;
            }
            $return_types[] = $this->generalize_type_from_value($scope, $parameter->get_value());
        }
        return Type_Combinator::union(...$return_types);
    }
    /**
     * @param array<mixed>|bool|float|int|string $value
     */
    private function generalize_type_from_value(Scope $scope, $value): Type
    {
        if (is_array($value) && $value !== []) {
            $has_only_string_key = true;
            foreach (array_keys($value) as $key) {
                if (is_int($key)) {
                    $has_only_string_key = false;
                    break;
                }
            }
            if ($has_only_string_key) {
                $key_types = [];
                $value_types = [];
                foreach ($value as $key => $element) {
                    $key_type = $scope->get_type_from_value($key);
                    $key_string_types = $key_type->get_constant_strings();
                    if (count($key_string_types) !== 1) {
                        throw new Should_Not_Happen_Exception();
                    }
                    $key_types[] = $key_string_types[0];
                    $value_types[] = $this->generalize_type_from_value($scope, $element);
                }
                return Constant_Array_Type_Builder::create_from_constant_array(new Constant_Array_Type($key_types, $value_types))->get_array();
            }
            return new Array_Type(Type_Combinator::union(...array_map(fn($item): Type => $this->generalize_type_from_value($scope, $item), array_keys($value))), Type_Combinator::union(...array_map(fn($item): Type => $this->generalize_type_from_value($scope, $item), array_values($value))));
        }
        if (class_exists(Env_Var_Processor::class) && is_string($value) && preg_match('/%env\((.*)\:.*\)%/U', $value, $matches) === 1 && strlen($matches[0]) === strlen($value)) {
            $provided_types = Env_Var_Processor::get_provided_types();
            return $this->type_string_resolver->resolve($provided_types[$matches[1]] ?? 'bool|int|float|string|array');
        }
        return $this->generalize_type($scope->get_type_from_value($value));
    }
    private function generalize_type(Type $type): Type
    {
        return Type_Traverser::map($type, function (Type $type, callable $traverse): Type {
            if ($type instanceof Constant_Array_Type) {
                if (count($type->get_value_types()) === 0) {
                    return new Array_Type(new Mixed_Type(), new Mixed_Type());
                }
                return new Array_Type($this->generalize_type($type->get_key_type()), $this->generalize_type($type->get_item_type()));
            }
            if ($type->is_constant_value()->yes()) {
                return $type->generalize(Generalize_Precision::less_specific());
            }
            return $traverse($type);
        });
    }
    private function get_has_type_from_method_call(Method_Call $method_call, Scope $scope): ?Type
    {
        if (!isset($method_call->get_args()[0]) || !$this->constant_hassers) {
            return null;
        }
        $parameter_keys = $this->parameter_map::get_parameter_keys_from_node($method_call->get_args()[0]->value, $scope);
        if ($parameter_keys === []) {
            return null;
        }
        $has = null;
        foreach ($parameter_keys as $parameter_key) {
            $parameter = $this->parameter_map->get_parameter($parameter_key);
            if ($has === null) {
                $has = $parameter !== null;
            } elseif ($has === true && $parameter === null || $has === false && $parameter !== null) {
                return null;
            }
        }
        return new Constant_Boolean_Type($has);
    }
}
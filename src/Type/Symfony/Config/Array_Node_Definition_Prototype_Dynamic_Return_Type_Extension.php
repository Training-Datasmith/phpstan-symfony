<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config;

use function count;
use function in_array;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Symfony\Config\Value_Object\Parent_Object_Type;
use Php_Stan\Type\Type;
final class Array_Node_Definition_Prototype_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    private const PROTOTYPE_METHODS = ['arrayPrototype', 'scalarPrototype', 'booleanPrototype', 'integerPrototype', 'floatPrototype', 'enumPrototype', 'variablePrototype'];
    private const MAPPING = ['variable' => 'Symfony\Component\Config\Definition\Builder\VariableNodeDefinition', 'scalar' => 'Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition', 'boolean' => 'Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition', 'integer' => 'Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition', 'float' => 'Symfony\Component\Config\Definition\Builder\FloatNodeDefinition', 'array' => 'Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition', 'enum' => 'Symfony\Component\Config\Definition\Builder\EnumNodeDefinition'];
    public function get_class(): string
    {
        return 'Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        if ($method_reflection->get_name() === 'prototype') {
            return true;
        }
        return in_array($method_reflection->get_name(), self::PROTOTYPE_METHODS, true);
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        $called_on_type = $scope->get_type($method_call->var);
        $default_type = Parameters_Acceptor_Selector::select_from_args($scope, $method_call->get_args(), $method_reflection->get_variants())->get_return_type();
        if ($method_reflection->get_name() === 'prototype') {
            if (!isset($method_call->get_args()[0])) {
                return $default_type;
            }
            $arg_strings = $scope->get_type($method_call->get_args()[0]->value)->get_constant_strings();
            if (count($arg_strings) === 1 && isset(self::MAPPING[$arg_strings[0]->get_value()])) {
                $type = $arg_strings[0]->get_value();
                return new Parent_Object_Type(self::MAPPING[$type], $called_on_type);
            }
        }
        $class_names = $default_type->get_object_class_names();
        if (count($class_names) !== 1) {
            return null;
        }
        return new Parent_Object_Type($class_names[0], $called_on_type);
    }
}
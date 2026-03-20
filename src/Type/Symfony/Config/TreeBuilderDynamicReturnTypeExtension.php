<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config;

use function count;
use Php_Parser\Node\Expr\Static_Call;
use Php_Parser\Node\Name;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Type\Dynamic_Static_Method_Return_Type_Extension;
use Php_Stan\Type\Symfony\Config\Value_Object\Tree_Builder_Type;
use Php_Stan\Type\Type;
final class Tree_Builder_Dynamic_Return_Type_Extension implements Dynamic_Static_Method_Return_Type_Extension
{
    private const MAPPING = ['variable' => 'Symfony\Component\Config\Definition\Builder\VariableNodeDefinition', 'scalar' => 'Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition', 'boolean' => 'Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition', 'integer' => 'Symfony\Component\Config\Definition\Builder\IntegerNodeDefinition', 'float' => 'Symfony\Component\Config\Definition\Builder\FloatNodeDefinition', 'array' => 'Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition', 'enum' => 'Symfony\Component\Config\Definition\Builder\EnumNodeDefinition'];
    public function get_class(): string
    {
        return 'Symfony\Component\Config\Definition\Builder\TreeBuilder';
    }
    public function is_static_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === '__construct';
    }
    public function get_type_from_static_method_call(Method_Reflection $method_reflection, Static_Call $method_call, Scope $scope): Type
    {
        if (!$method_call->class instanceof Name) {
            throw new Should_Not_Happen_Exception();
        }
        $class_name = $scope->resolve_name($method_call->class);
        $type = 'array';
        if (isset($method_call->get_args()[1])) {
            $arg_strings = $scope->get_type($method_call->get_args()[1]->value)->get_constant_strings();
            if (count($arg_strings) === 1 && isset(self::MAPPING[$arg_strings[0]->get_value()])) {
                $type = $arg_strings[0]->get_value();
            }
        }
        return new Tree_Builder_Type($class_name, self::MAPPING[$type]);
    }
}
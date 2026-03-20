<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Symfony\Config\Value_Object\Parent_Object_Type;
use Php_Stan\Type\Symfony\Config\Value_Object\Tree_Builder_Type;
use Php_Stan\Type\Type;
final class Tree_Builder_Get_Root_Node_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return 'Symfony\Component\Config\Definition\Builder\TreeBuilder';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getRootNode';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        $called_on_type = $scope->get_type($method_call->var);
        if ($called_on_type instanceof Tree_Builder_Type) {
            return new Parent_Object_Type($called_on_type->get_root_node_class_name(), $called_on_type);
        }
        return null;
    }
}
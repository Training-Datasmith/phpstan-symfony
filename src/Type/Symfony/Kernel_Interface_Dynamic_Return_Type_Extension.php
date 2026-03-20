<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
final class Kernel_Interface_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return 'Symfony\Component\HttpKernel\KernelInterface';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'locateResource';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        $first_arg_type = isset($method_call->get_args()[2]) ? $scope->get_type($method_call->get_args()[2]->value) : new Constant_Boolean_Type(true);
        $is_true_type = (new Constant_Boolean_Type(true))->is_super_type_of($first_arg_type)->result;
        $is_false_type = (new Constant_Boolean_Type(false))->is_super_type_of($first_arg_type)->result;
        $compare_types = $is_true_type->compare_to($is_false_type);
        if ($compare_types === $is_true_type) {
            return new String_Type();
        }
        if ($compare_types === $is_false_type) {
            return new Array_Type(new Integer_Type(), new String_Type());
        }
        return null;
    }
}
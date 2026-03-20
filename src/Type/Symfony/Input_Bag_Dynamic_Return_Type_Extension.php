<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function in_array;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Float_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Union_Type;
final class Input_Bag_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return 'Symfony\Component\HttpFoundation\InputBag';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return in_array($method_reflection->get_name(), ['get', 'all'], true);
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        if ($method_reflection->get_name() === 'get') {
            return $this->get_get_type_from_method_call($method_reflection, $method_call, $scope);
        }
        if ($method_reflection->get_name() === 'all') {
            return $this->get_all_type_from_method_call($method_call);
        }
        throw new Should_Not_Happen_Exception();
    }
    private function get_get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        if (isset($method_call->get_args()[1])) {
            $arg_type = $scope->get_type($method_call->get_args()[1]->value);
            $is_null = (new Null_Type())->is_super_type_of($arg_type);
            if ($is_null->no()) {
                return Type_Combinator::remove_null(Parameters_Acceptor_Selector::select_from_args($scope, $method_call->get_args(), $method_reflection->get_variants())->get_return_type());
            }
        }
        return null;
    }
    private function get_all_type_from_method_call(Method_Call $method_call): Type
    {
        if (isset($method_call->get_args()[0])) {
            return new Array_Type(new Mixed_Type(), new Mixed_Type(true));
        }
        return new Array_Type(new String_Type(), new Union_Type([new Array_Type(new Mixed_Type(), new Mixed_Type(true)), new Boolean_Type(), new Float_Type(), new Integer_Type(), new String_Type()]));
    }
}
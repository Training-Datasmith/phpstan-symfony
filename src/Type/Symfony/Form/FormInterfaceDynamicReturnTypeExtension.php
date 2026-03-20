<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Form;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Generic\Generic_Object_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Union_Type;
use Symfony\Component\Form\Form_Error;
use Symfony\Component\Form\Form_Error_Iterator;
use Symfony\Component\Form\Form_Interface;
final class Form_Interface_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return Form_Interface::class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getErrors';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): Type
    {
        if (!isset($method_call->get_args()[1])) {
            return new Generic_Object_Type(Form_Error_Iterator::class, [new Object_Type(Form_Error::class)]);
        }
        $first_arg_type = $scope->get_type($method_call->get_args()[0]->value);
        $second_arg_type = $scope->get_type($method_call->get_args()[1]->value);
        $first_is_true_type = (new Constant_Boolean_Type(true))->is_super_type_of($first_arg_type)->result;
        $first_is_false_type = (new Constant_Boolean_Type(false))->is_super_type_of($first_arg_type)->result;
        $second_is_true_type = (new Constant_Boolean_Type(true))->is_super_type_of($second_arg_type)->result;
        $second_is_false_type = (new Constant_Boolean_Type(false))->is_super_type_of($second_arg_type)->result;
        $first_compare_type = $first_is_true_type->compare_to($first_is_false_type);
        $second_compare_type = $second_is_true_type->compare_to($second_is_false_type);
        if ($first_compare_type === $first_is_true_type && $second_compare_type === $second_is_false_type) {
            return new Generic_Object_Type(Form_Error_Iterator::class, [new Union_Type([new Object_Type(Form_Error::class), new Object_Type(Form_Error_Iterator::class)])]);
        }
        return new Generic_Object_Type(Form_Error_Iterator::class, [new Object_Type(Form_Error::class)]);
    }
}
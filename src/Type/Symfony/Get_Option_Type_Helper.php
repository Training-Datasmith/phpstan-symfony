<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function method_exists;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Boolean_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Union_Type;
use Symfony\Component\Console\Input\Input_Option;
class Get_Option_Type_Helper
{
    public function get_option_type(Scope $scope, Input_Option $option): Type
    {
        if (!$option->accept_value()) {
            if (method_exists($option, 'isNegatable') && $option->is_negatable()) {
                return new Union_Type([new Boolean_Type(), new Null_Type()]);
            }
            return new Boolean_Type();
        }
        $opt_type = Type_Combinator::union(new String_Type(), new Null_Type());
        if ($option->is_value_required() && ($option->is_array() || $option->get_default() !== null)) {
            $opt_type = Type_Combinator::remove_null($opt_type);
        }
        if ($option->is_array()) {
            $opt_type = new Array_Type(new Integer_Type(), $opt_type);
        }
        return Type_Combinator::union($opt_type, $scope->get_type_from_value($option->get_default()));
    }
}
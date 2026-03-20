<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Accessory\Accessory_Array_List_Type;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Generic\Generic_Class_String_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
final class Envelope_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return 'Symfony\Component\Messenger\Envelope';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'all';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): Type
    {
        if (count($method_call->get_args()) === 0) {
            return new Array_Type(new Generic_Class_String_Type(new Object_Type('Symfony\Component\Messenger\Stamp\StampInterface')), Type_Combinator::intersect(new Array_Type(new Integer_Type(), new Object_Type('Symfony\Component\Messenger\Stamp\StampInterface')), new Accessory_Array_List_Type()));
        }
        $arg_type = $scope->get_type($method_call->get_args()[0]->value);
        if (count($arg_type->get_constant_strings()) === 0) {
            return Type_Combinator::intersect(new Array_Type(new Integer_Type(), new Object_Type('Symfony\Component\Messenger\Stamp\StampInterface')), new Accessory_Array_List_Type());
        }
        $object_types = [];
        foreach ($arg_type->get_constant_strings() as $constant_string) {
            $object_types[] = new Object_Type($constant_string->get_value());
        }
        return Type_Combinator::intersect(new Array_Type(new Integer_Type(), Type_Combinator::union(...$object_types)), new Accessory_Array_List_Type());
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Class_Const_Fetch;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Identifier;
use Php_Parser\Node\Name;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Symfony\Component\Http_Foundation\Cookie;
use Symfony\Component\Http_Foundation\Response_Header_Bag;
final class Response_Header_Bag_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return Response_Header_Bag::class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getCookies';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): Type
    {
        if (isset($method_call->get_args()[0])) {
            $node = $method_call->get_args()[0]->value;
            if ($node instanceof Class_Const_Fetch && $node->class instanceof Name && $node->name instanceof Identifier && $node->class->to_string() === Response_Header_Bag::class && $node->name->name === 'COOKIES_ARRAY') {
                return new Array_Type(new String_Type(), new Array_Type(new String_Type(), new Array_Type(new String_Type(), new Object_Type(Cookie::class))));
            }
        }
        return new Array_Type(new Integer_Type(), new Object_Type(Cookie::class));
    }
}
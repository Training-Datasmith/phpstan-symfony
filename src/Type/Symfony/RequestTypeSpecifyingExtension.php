<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Analyser\Specified_Types;
use Php_Stan\Analyser\Type_Specifier;
use Php_Stan\Analyser\Type_Specifier_Aware_Extension;
use Php_Stan\Analyser\Type_Specifier_Context;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Type\Method_Type_Specifying_Extension;
use Php_Stan\Type\Type_Combinator;
final class Request_Type_Specifying_Extension implements Method_Type_Specifying_Extension, Type_Specifier_Aware_Extension
{
    private const REQUEST_CLASS = 'Symfony\Component\HttpFoundation\Request';
    private const HAS_METHOD_NAME = 'hasSession';
    private const GET_METHOD_NAME = 'getSession';
    private Type_Specifier $type_specifier;
    public function get_class(): string
    {
        return self::REQUEST_CLASS;
    }
    public function is_method_supported(Method_Reflection $method_reflection, Method_Call $node, Type_Specifier_Context $context): bool
    {
        return $method_reflection->get_name() === self::HAS_METHOD_NAME && !$context->null();
    }
    public function specify_types(Method_Reflection $method_reflection, Method_Call $node, Scope $scope, Type_Specifier_Context $context): Specified_Types
    {
        $method_variants = $method_reflection->get_declaring_class()->get_native_method(self::GET_METHOD_NAME)->get_variants();
        $return_type = Parameters_Acceptor_Selector::select_from_args($scope, $node->get_args(), $method_variants)->get_return_type();
        if (!Type_Combinator::contains_null($return_type)) {
            return new Specified_Types();
        }
        return $this->type_specifier->create(new Method_Call($node->var, self::GET_METHOD_NAME), Type_Combinator::remove_null($return_type), $context, $scope);
    }
    public function set_type_specifier(Type_Specifier $type_specifier): void
    {
        $this->type_specifier = $type_specifier;
    }
}
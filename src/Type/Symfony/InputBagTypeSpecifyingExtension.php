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
use Php_Stan\Type\Method_Type_Specifying_Extension;
use Php_Stan\Type\Null_Type;
use Symfony\Component\Http_Foundation\Input_Bag;
final class Input_Bag_Type_Specifying_Extension implements Method_Type_Specifying_Extension, Type_Specifier_Aware_Extension
{
    private const INPUT_BAG_CLASS = Input_Bag::class;
    private const HAS_METHOD_NAME = 'has';
    private const GET_METHOD_NAME = 'get';
    private Type_Specifier $type_specifier;
    public function get_class(): string
    {
        return self::INPUT_BAG_CLASS;
    }
    public function is_method_supported(Method_Reflection $method_reflection, Method_Call $node, Type_Specifier_Context $context): bool
    {
        return $method_reflection->get_name() === self::HAS_METHOD_NAME && $context->false();
    }
    public function specify_types(Method_Reflection $method_reflection, Method_Call $node, Scope $scope, Type_Specifier_Context $context): Specified_Types
    {
        return $this->type_specifier->create(new Method_Call($node->var, self::GET_METHOD_NAME, $node->get_args()), new Null_Type(), $context->negate(), $scope);
    }
    public function set_type_specifier(Type_Specifier $type_specifier): void
    {
        $this->type_specifier = $type_specifier;
    }
}
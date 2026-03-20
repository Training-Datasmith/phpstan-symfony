<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Identifier;
use Php_Stan\Analyser\Scope;
use Php_Stan\Type\Expression_Type_Resolver_Extension;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Php_Stan\Type\Union_Type;
final class Browser_Kit_Assertion_Trait_Return_Type_Extension implements Expression_Type_Resolver_Extension
{
    private const TRAIT_NAME = 'Symfony\Bundle\FrameworkBundle\Test\BrowserKitAssertionsTrait';
    private const TRAIT_METHOD_NAME = 'getclient';
    public function get_type(Expr $expr, Scope $scope): ?Type
    {
        if ($this->is_supported($expr, $scope)) {
            $args = $expr->get_args();
            if (count($args) > 0) {
                return Type_Combinator::intersect($scope->get_type($args[0]->value), new Union_Type([new Object_Type('Symfony\Component\BrowserKit\AbstractBrowser'), new Null_Type()]));
            }
            return new Object_Type('Symfony\Component\BrowserKit\AbstractBrowser');
        }
        return null;
    }
    /**
     * @phpstan-assert-if-true =MethodCall $expr
     */
    private function is_supported(Expr $expr, Scope $scope): bool
    {
        if (!$expr instanceof Method_Call || !$expr->name instanceof Identifier || $expr->name->to_lower_string() !== self::TRAIT_METHOD_NAME) {
            return false;
        }
        if (!$scope->is_in_class()) {
            return false;
        }
        $method_reflection = $scope->get_method_reflection($scope->get_type($expr->var), $expr->name->to_string());
        if ($method_reflection === null) {
            return false;
        }
        $reflection_class = $method_reflection->get_declaring_class()->get_native_reflection();
        if (!$reflection_class->has_method(self::TRAIT_METHOD_NAME)) {
            return false;
        }
        $trait_method_reflection = $reflection_class->get_method(self::TRAIT_METHOD_NAME);
        $declaring_class_reflection = $trait_method_reflection->get_better_reflection()->get_declaring_class();
        return $declaring_class_reflection->get_name() === self::TRAIT_NAME;
    }
}
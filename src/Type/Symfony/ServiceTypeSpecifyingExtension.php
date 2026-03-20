<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Analyser\Specified_Types;
use Php_Stan\Analyser\Type_Specifier;
use Php_Stan\Analyser\Type_Specifier_Aware_Extension;
use Php_Stan\Analyser\Type_Specifier_Context;
use Php_Stan\Node\Printer\Printer;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Method_Type_Specifying_Extension;
final class Service_Type_Specifying_Extension implements Method_Type_Specifying_Extension, Type_Specifier_Aware_Extension
{
    /** @var class-string */
    private string $class_name;
    private Printer $printer;
    private Type_Specifier $type_specifier;
    /**
     * @param class-string $className
     */
    public function __construct(string $class_name, Printer $printer)
    {
        $this->class_name = $class_name;
        $this->printer = $printer;
    }
    public function get_class(): string
    {
        return $this->class_name;
    }
    public function is_method_supported(Method_Reflection $method_reflection, Method_Call $node, Type_Specifier_Context $context): bool
    {
        return $method_reflection->get_name() === 'has' && !$context->null();
    }
    public function specify_types(Method_Reflection $method_reflection, Method_Call $node, Scope $scope, Type_Specifier_Context $context): Specified_Types
    {
        if (!isset($node->get_args()[0])) {
            return new Specified_Types();
        }
        $arg_type = $scope->get_type($node->get_args()[0]->value);
        return $this->type_specifier->create(Helper::create_marker_node($node->var, $arg_type, $this->printer), $arg_type, $context, $scope);
    }
    public function set_type_specifier(Type_Specifier $type_specifier): void
    {
        $this->type_specifier = $type_specifier;
    }
}
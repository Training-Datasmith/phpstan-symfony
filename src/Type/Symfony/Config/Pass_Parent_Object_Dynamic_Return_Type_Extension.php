<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config;

use function count;
use function in_array;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Symfony\Config\Value_Object\Parent_Object_Type;
use Php_Stan\Type\Type;
final class Pass_Parent_Object_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    /** @var class-string */
    private string $class_name;
    /** @var string[] */
    private array $methods;
    /**
     * @param class-string $className
     * @param string[] $methods
     */
    public function __construct(string $class_name, array $methods)
    {
        $this->class_name = $class_name;
        $this->methods = $methods;
    }
    public function get_class(): string
    {
        return $this->class_name;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return in_array($method_reflection->get_name(), $this->methods, true);
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        $called_on_type = $scope->get_type($method_call->var);
        $default_type = Parameters_Acceptor_Selector::select_from_args($scope, $method_call->get_args(), $method_reflection->get_variants())->get_return_type();
        $class_names = $default_type->get_object_class_names();
        if (count($class_names) !== 1) {
            return null;
        }
        return new Parent_Object_Type($class_names[0], $called_on_type);
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use function str_contains;
use function strrpos;
use function substr_replace;
class Extension_Get_Configuration_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    private Reflection_Provider $reflection_provider;
    public function __construct(Reflection_Provider $reflection_provider)
    {
        $this->reflection_provider = $reflection_provider;
    }
    public function get_class(): string
    {
        return 'Symfony\Component\DependencyInjection\Extension\Extension';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getConfiguration' && $method_reflection->get_declaring_class()->get_name() === 'Symfony\Component\DependencyInjection\Extension\Extension';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        $types = [];
        $extension_type = $scope->get_type($method_call->var);
        $classes = $extension_type->get_object_class_names();
        foreach ($classes as $extension_name) {
            if (str_contains($extension_name, "\x00")) {
                $types[] = new Null_Type();
                continue;
            }
            $last_backslash = strrpos($extension_name, '\\');
            if ($last_backslash === false) {
                $types[] = new Null_Type();
                continue;
            }
            $configuration_name = substr_replace($extension_name, '\Configuration', $last_backslash);
            if (!$this->reflection_provider->has_class($configuration_name)) {
                $types[] = new Null_Type();
                continue;
            }
            $reflection = $this->reflection_provider->get_class($configuration_name);
            if ($this->has_required_constructor($reflection)) {
                $types[] = new Null_Type();
                continue;
            }
            $types[] = new Object_Type($configuration_name);
        }
        return Type_Combinator::union(...$types);
    }
    private function has_required_constructor(Class_Reflection $class): bool
    {
        if (!$class->has_constructor()) {
            return false;
        }
        $constructor = $class->get_constructor();
        foreach ($constructor->get_variants() as $variant) {
            $any_required = false;
            foreach ($variant->get_parameters() as $parameter) {
                if (!$parameter->is_optional()) {
                    $any_required = true;
                    break;
                }
            }
            if (!$any_required) {
                return false;
            }
        }
        return true;
    }
}
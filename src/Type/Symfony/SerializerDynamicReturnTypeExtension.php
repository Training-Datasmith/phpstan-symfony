<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Mixed_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use function substr;
class Serializer_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    /** @var class-string */
    private string $class;
    private string $method;
    /**
     * @param class-string $class
     */
    public function __construct(string $class, string $method)
    {
        $this->class = $class;
        $this->method = $method;
    }
    public function get_class(): string
    {
        return $this->class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === $this->method;
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): Type
    {
        if (!isset($method_call->get_args()[1])) {
            return new Mixed_Type();
        }
        $arg_type = $scope->get_type($method_call->get_args()[1]->value);
        if (count($arg_type->get_constant_strings()) === 0) {
            return new Mixed_Type();
        }
        $types = [];
        foreach ($arg_type->get_constant_strings() as $constant_string) {
            $types[] = $this->get_type($constant_string->get_value());
        }
        return Type_Combinator::union(...$types);
    }
    private function get_type(string $object_name): Type
    {
        if (substr($object_name, -2) === '[]') {
            // The key type is determined by the data
            return new Array_Type(new Mixed_Type(false), $this->get_type(substr($object_name, 0, -2)));
        }
        return new Object_Type($object_name);
    }
}
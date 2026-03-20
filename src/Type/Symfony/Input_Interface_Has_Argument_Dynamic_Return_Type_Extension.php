<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function array_unique;
use function count;
use function in_array;
use InvalidArgumentException;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Symfony\Console_Application_Resolver;
use Php_Stan\Type\Constant\Constant_Boolean_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Type;
final class Input_Interface_Has_Argument_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    private Console_Application_Resolver $console_application_resolver;
    public function __construct(Console_Application_Resolver $console_application_resolver)
    {
        $this->console_application_resolver = $console_application_resolver;
    }
    public function get_class(): string
    {
        return \Symfony\Component\Console\Input\Input_Interface::class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'hasArgument';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        if (!isset($method_call->get_args()[0])) {
            return null;
        }
        $class_reflection = $scope->get_class_reflection();
        if ($class_reflection === null) {
            return null;
        }
        $arg_strings = $scope->get_type($method_call->get_args()[0]->value)->get_constant_strings();
        if (count($arg_strings) !== 1) {
            return null;
        }
        $arg_name = $arg_strings[0]->get_value();
        if ($arg_name === 'command') {
            $method = $scope->get_function();
            if ($method instanceof Method_Reflection && ($method->get_name() === 'interact' || $method->get_name() === 'initialize') && in_array(\Symfony\Component\Console\Command\Command::class, $method->get_declaring_class()->get_parent_classes_names(), true)) {
                return null;
            }
        }
        $return_types = [];
        foreach ($this->console_application_resolver->find_commands($class_reflection) as $command) {
            try {
                $command->merge_application_definition();
                $command->get_definition()->get_argument($arg_name);
                $return_types[] = true;
            } catch (InvalidArgumentException $e) {
                $return_types[] = false;
            }
        }
        if (count($return_types) === 0) {
            return null;
        }
        $return_types = array_unique($return_types);
        return count($return_types) === 1 ? new Constant_Boolean_Type($return_types[0]) : null;
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use function in_array;
use InvalidArgumentException;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Symfony\Console_Application_Resolver;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
final class Input_Interface_Get_Argument_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
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
        return $method_reflection->get_name() === 'getArgument';
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
        $arg_types = [];
        $can_be_null_in_interact = false;
        foreach ($this->console_application_resolver->find_commands($class_reflection) as $command) {
            try {
                $command->merge_application_definition();
                $argument = $command->get_definition()->get_argument($arg_name);
                if ($argument->is_array()) {
                    $arg_type = new Array_Type(new Integer_Type(), new String_Type());
                    if (!$argument->is_required() && $argument->get_default() !== []) {
                        $arg_type = Type_Combinator::union($arg_type, $scope->get_type_from_value($argument->get_default()));
                    }
                } else {
                    $arg_type = new String_Type();
                    if (!$argument->is_required()) {
                        $arg_type = Type_Combinator::union($arg_type, $scope->get_type_from_value($argument->get_default()));
                    } else {
                        $can_be_null_in_interact = true;
                    }
                }
                $arg_types[] = $arg_type;
            } catch (InvalidArgumentException $e) {
                // noop
            }
        }
        if (count($arg_types) === 0) {
            return null;
        }
        $method = $scope->get_function();
        if ($can_be_null_in_interact && $method instanceof Method_Reflection && ($method->get_name() === 'interact' || $method->get_name() === 'initialize') && in_array(\Symfony\Component\Console\Command\Command::class, $method->get_declaring_class()->get_parent_classes_names(), true)) {
            $arg_types[] = new Null_Type();
        }
        return Type_Combinator::union(...$arg_types);
    }
}
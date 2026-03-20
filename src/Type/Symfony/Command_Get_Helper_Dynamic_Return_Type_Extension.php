<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use function get_class;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Symfony\Console_Application_Resolver;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
use Throwable;
final class Command_Get_Helper_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    private Console_Application_Resolver $console_application_resolver;
    public function __construct(Console_Application_Resolver $console_application_resolver)
    {
        $this->console_application_resolver = $console_application_resolver;
    }
    public function get_class(): string
    {
        return \Symfony\Component\Console\Command\Command::class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getHelper';
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
        $return_types = [];
        foreach ($this->console_application_resolver->find_commands($class_reflection) as $command) {
            try {
                $command->merge_application_definition();
                $return_types[] = new Object_Type(get_class($command->get_helper($arg_name)));
            } catch (Throwable $e) {
                // no-op
            }
        }
        return count($return_types) > 0 ? Type_Combinator::union(...$return_types) : null;
    }
}
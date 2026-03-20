<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use InvalidArgumentException;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Symfony\Console_Application_Resolver;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
final class Input_Interface_Get_Option_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    private Console_Application_Resolver $console_application_resolver;
    private Get_Option_Type_Helper $get_option_type_helper;
    public function __construct(Console_Application_Resolver $console_application_resolver, Get_Option_Type_Helper $get_option_type_helper)
    {
        $this->console_application_resolver = $console_application_resolver;
        $this->get_option_type_helper = $get_option_type_helper;
    }
    public function get_class(): string
    {
        return \Symfony\Component\Console\Input\Input_Interface::class;
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'getOption';
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
        $opt_strings = $scope->get_type($method_call->get_args()[0]->value)->get_constant_strings();
        if (count($opt_strings) !== 1) {
            return null;
        }
        $opt_name = $opt_strings[0]->get_value();
        $opt_types = [];
        foreach ($this->console_application_resolver->find_commands($class_reflection) as $command) {
            try {
                $command->merge_application_definition();
                $option = $command->get_definition()->get_option($opt_name);
                $opt_types[] = $this->get_option_type_helper->get_option_type($scope, $option);
            } catch (InvalidArgumentException $e) {
                // noop
            }
        }
        return count($opt_types) > 0 ? Type_Combinator::union(...$opt_types) : null;
    }
}
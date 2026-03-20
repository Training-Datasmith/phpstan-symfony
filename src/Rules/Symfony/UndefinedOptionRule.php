<?php

declare (strict_types=1);
namespace Php_Stan\Rules\Symfony;

use function count;
use InvalidArgumentException;
use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Node\Printer\Printer;
use Php_Stan\Rules\Rule;
use Php_Stan\Rules\Rule_Error_Builder;
use Php_Stan\Symfony\Console_Application_Resolver;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Symfony\Helper;
use function sprintf;
/**
 * @implements Rule<MethodCall>
 */
final class Undefined_Option_Rule implements Rule
{
    private Console_Application_Resolver $console_application_resolver;
    private Printer $printer;
    public function __construct(Console_Application_Resolver $console_application_resolver, Printer $printer)
    {
        $this->console_application_resolver = $console_application_resolver;
        $this->printer = $printer;
    }
    public function get_node_type(): string
    {
        return Method_Call::class;
    }
    public function process_node(Node $node, Scope $scope): array
    {
        $class_reflection = $scope->get_class_reflection();
        if ($class_reflection === null) {
            return [];
        }
        if (!(new Object_Type(\Symfony\Component\Console\Command\Command::class))->is_super_type_of(new Object_Type($class_reflection->get_name()))->yes()) {
            return [];
        }
        if (!(new Object_Type(\Symfony\Component\Console\Input\Input_Interface::class))->is_super_type_of($scope->get_type($node->var))->yes()) {
            return [];
        }
        if (!$node->name instanceof Node\Identifier || $node->name->name !== 'getOption') {
            return [];
        }
        if (!isset($node->get_args()[0])) {
            return [];
        }
        $opt_type = $scope->get_type($node->get_args()[0]->value);
        $opt_strings = $opt_type->get_constant_strings();
        if (count($opt_strings) !== 1) {
            return [];
        }
        $opt_name = $opt_strings[0]->get_value();
        $errors = [];
        foreach ($this->console_application_resolver->find_commands($class_reflection) as $name => $command) {
            try {
                $command->merge_application_definition();
                $command->get_definition()->get_option($opt_name);
            } catch (InvalidArgumentException $e) {
                if ($scope->get_type(Helper::create_marker_node($node->var, $opt_type, $this->printer))->equals($opt_type)) {
                    continue;
                }
                $errors[] = Rule_Error_Builder::message(sprintf('Command "%s" does not define option "%s".', $name, $opt_name))->identifier('symfonyConsole.optionNotFound')->build();
            }
        }
        return $errors;
    }
}
<?php

declare (strict_types=1);
namespace Php_Stan\Rules\Symfony;

use function count;
use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Rules\Rule;
use Php_Stan\Rules\Rule_Error_Builder;
use Php_Stan\Type\Array_Type;
use Php_Stan\Type\Constant\Constant_Integer_Type;
use Php_Stan\Type\Integer_Type;
use Php_Stan\Type\Null_Type;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\String_Type;
use Php_Stan\Type\Union_Type;
use Php_Stan\Type\Verbosity_Level;
use function sprintf;
/**
 * @implements Rule<MethodCall>
 */
final class Invalid_Argument_Default_Value_Rule implements Rule
{
    public function get_node_type(): string
    {
        return Method_Call::class;
    }
    public function process_node(Node $node, Scope $scope): array
    {
        if (!(new Object_Type(\Symfony\Component\Console\Command\Command::class))->is_super_type_of($scope->get_type($node->var))->yes()) {
            return [];
        }
        if (!$node->name instanceof Node\Identifier || $node->name->name !== 'addArgument') {
            return [];
        }
        if (!isset($node->get_args()[3])) {
            return [];
        }
        $mode_type = isset($node->get_args()[1]) ? $scope->get_type($node->get_args()[1]->value) : new Null_Type();
        if ($mode_type->is_null()->yes()) {
            $mode_type = new Constant_Integer_Type(2);
            // InputArgument::OPTIONAL
        }
        $mode_types = $mode_type->get_constant_scalar_types();
        if (count($mode_types) !== 1) {
            return [];
        }
        if (!$mode_types[0] instanceof Constant_Integer_Type) {
            return [];
        }
        $mode = $mode_types[0]->get_value();
        $default_type = $scope->get_type($node->get_args()[3]->value);
        // not an array
        if (($mode & 4) !== 4 && !(new Union_Type([new String_Type(), new Null_Type()]))->is_super_type_of($default_type)->yes()) {
            return [Rule_Error_Builder::message(sprintf('Parameter #4 $default of method Symfony\Component\Console\Command\Command::addArgument() expects string|null, %s given.', $default_type->describe(Verbosity_Level::type_only())))->identifier('argument.type')->build()];
        }
        // is array
        if (($mode & 4) === 4 && !(new Union_Type([new Array_Type(new Integer_Type(), new String_Type()), new Null_Type()]))->is_super_type_of($default_type)->yes()) {
            return [Rule_Error_Builder::message(sprintf('Parameter #4 $default of method Symfony\Component\Console\Command\Command::addArgument() expects array<int, string>|null, %s given.', $default_type->describe(Verbosity_Level::type_only())))->identifier('argument.type')->build()];
        }
        return [];
    }
}
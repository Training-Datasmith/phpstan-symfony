<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use function is_null;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Identifier;
use Php_Stan\Analyser\Scope;
use Php_Stan\Symfony\Message_Map;
use Php_Stan\Symfony\Message_Map_Factory;
use Php_Stan\Type\Expression_Type_Resolver_Extension;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
final class Messenger_Handle_Trait_Return_Type_Extension implements Expression_Type_Resolver_Extension
{
    private const TRAIT_NAME = 'Symfony\Component\Messenger\HandleTrait';
    private const TRAIT_METHOD_NAME = 'handle';
    private Message_Map_Factory $message_map_factory;
    private ?Message_Map $message_map = null;
    public function __construct(Message_Map_Factory $symfony_message_map_factory)
    {
        $this->message_map_factory = $symfony_message_map_factory;
    }
    public function get_type(Expr $expr, Scope $scope): ?Type
    {
        if (!$this->is_supported($expr, $scope)) {
            return null;
        }
        $args = $expr->get_args();
        if (count($args) !== 1) {
            return null;
        }
        $arg = $args[0]->value;
        $arg_class_names = $scope->get_type($arg)->get_object_class_names();
        if (count($arg_class_names) === 0) {
            return null;
        }
        $message_map = $this->get_message_map();
        $return_types = [];
        foreach ($arg_class_names as $arg_class_name) {
            $return_type = $message_map->get_type_for_class($arg_class_name);
            if (is_null($return_type)) {
                return null;
            }
            $return_types[] = $return_type;
        }
        return Type_Combinator::union(...$return_types);
    }
    private function get_message_map(): Message_Map
    {
        if ($this->message_map === null) {
            $this->message_map = $this->message_map_factory->create();
        }
        return $this->message_map;
    }
    /**
     * @phpstan-assert-if-true =MethodCall $expr
     */
    private function is_supported(Expr $expr, Scope $scope): bool
    {
        if (!$expr instanceof Method_Call || !$expr->name instanceof Identifier || $expr->name->to_lower_string() !== self::TRAIT_METHOD_NAME) {
            return false;
        }
        if (!$scope->is_in_class()) {
            return false;
        }
        $method_reflection = $scope->get_method_reflection($scope->get_type($expr->var), $expr->name->to_string());
        if ($method_reflection === null) {
            return false;
        }
        $reflection_class = $method_reflection->get_declaring_class()->get_native_reflection();
        if (!$reflection_class->has_method(self::TRAIT_METHOD_NAME)) {
            return false;
        }
        $trait_method_reflection = $reflection_class->get_method(self::TRAIT_METHOD_NAME);
        $declaring_class_reflection = $trait_method_reflection->get_better_reflection()->get_declaring_class();
        return $declaring_class_reflection->get_name() === self::TRAIT_NAME;
    }
}
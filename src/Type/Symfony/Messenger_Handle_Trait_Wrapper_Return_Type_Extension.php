<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function count;
use function in_array;
use function is_null;
use Php_Parser\Node\Expr;
use Php_Parser\Node\Expr\Method_Call;
use Php_Parser\Node\Identifier;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Reflection_Provider;
use Php_Stan\Symfony\Message_Map;
use Php_Stan\Symfony\Message_Map_Factory;
use Php_Stan\Type\Expression_Type_Resolver_Extension;
use Php_Stan\Type\Type;
use Php_Stan\Type\Type_Combinator;
/**
 * Configurable extension for resolving return types of methods that internally use HandleTrait.
 *
 * Configured via PHPStan parameters under symfony.messenger.handleTraitWrappers with
 * "class::method" patterns, e.g.:
 * - App\Bus\QueryBus::dispatch
 * - App\Bus\QueryBus::query
 * - App\Bus\CommandBus::execute
 * - App\Bus\CommandBus::handle
 */
final class Messenger_Handle_Trait_Wrapper_Return_Type_Extension implements Expression_Type_Resolver_Extension
{
    private Message_Map_Factory $message_map_factory;
    private ?Message_Map $message_map = null;
    /** @var array<string> */
    private array $wrappers;
    private Reflection_Provider $reflection_provider;
    /** @param array{handleTraitWrappers: array<string>}|null $messenger */
    public function __construct(Message_Map_Factory $message_map_factory, ?array $messenger, Reflection_Provider $reflection_provider)
    {
        $this->message_map_factory = $message_map_factory;
        $this->wrappers = $messenger['handleTraitWrappers'] ?? [];
        $this->reflection_provider = $reflection_provider;
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
        $return_types = [];
        foreach ($arg_class_names as $arg_class_name) {
            $message_map = $this->get_message_map();
            $return_type = $message_map->get_type_for_class($arg_class_name);
            if (is_null($return_type)) {
                return null;
            }
            $return_types[] = $return_type;
        }
        return Type_Combinator::union(...$return_types);
    }
    /**
     * @phpstan-assert-if-true =MethodCall $expr
     */
    private function is_supported(Expr $expr, Scope $scope): bool
    {
        if ($this->wrappers === []) {
            return false;
        }
        if (!$expr instanceof Method_Call || !$expr->name instanceof Identifier) {
            return false;
        }
        $method_name = $expr->name->name;
        $var_type = $scope->get_type($expr->var);
        $class_names = $var_type->get_object_class_names();
        if (count($class_names) === 0) {
            return false;
        }
        foreach ($class_names as $class_name) {
            if (!$this->is_class_method_supported($class_name, $method_name)) {
                return false;
            }
        }
        return true;
    }
    private function is_class_method_supported(string $class_name, string $method_name): bool
    {
        $class_method_combination = $class_name . '::' . $method_name;
        // Check if this exact class::method combination is configured
        if (in_array($class_method_combination, $this->wrappers, true)) {
            return true;
        }
        // Check if any interface implemented by this class::method is configured
        if ($this->reflection_provider->has_class($class_name)) {
            $class_reflection = $this->reflection_provider->get_class($class_name);
            foreach ($class_reflection->get_interfaces() as $interface) {
                $interface_method_combination = $interface->get_name() . '::' . $method_name;
                if (in_array($interface_method_combination, $this->wrappers, true)) {
                    return true;
                }
            }
        }
        return false;
    }
    private function get_message_map(): Message_Map
    {
        if ($this->message_map === null) {
            $this->message_map = $this->message_map_factory->create();
        }
        return $this->message_map;
    }
}
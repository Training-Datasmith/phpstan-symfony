<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function class_exists;
use function count;
use function interface_exists;
use function is_array;
use function is_int;
use function is_string;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Reflection_Provider;
use Symfony\Component\Messenger\Handler\Message_Subscriber_Interface;
final class Message_Map_Factory
{
    private const MESSENGER_HANDLER_TAG = 'messenger.message_handler';
    private const DEFAULT_HANDLER_METHOD = '__invoke';
    private Reflection_Provider $reflection_provider;
    private Service_Map $service_map;
    public function __construct(Service_Map $symfony_service_map, Reflection_Provider $reflection_provider)
    {
        $this->service_map = $symfony_service_map;
        $this->reflection_provider = $reflection_provider;
    }
    public function create(): Message_Map
    {
        $return_types_map = [];
        foreach ($this->service_map->get_services() as $service) {
            $service_class = $service->get_class();
            if ($service_class === null) {
                continue;
            }
            foreach ($service->get_tags() as $tag) {
                if ($tag->get_name() !== self::MESSENGER_HANDLER_TAG) {
                    continue;
                }
                if (!$this->reflection_provider->has_class($service_class)) {
                    continue;
                }
                $reflection_class = $this->reflection_provider->get_class($service_class);
                /** @var array{handles?: class-string, method?: string} $tagAttributes */
                $tag_attributes = $tag->get_attributes();
                if (isset($tag_attributes['handles'])) {
                    $handles = [$tag_attributes['handles'] => ['method' => $tag_attributes['method'] ?? self::DEFAULT_HANDLER_METHOD]];
                } else {
                    $handles = $this->guess_handled_messages($reflection_class);
                }
                foreach ($handles as $message_class_name => $options) {
                    $method_name = $options['method'] ?? self::DEFAULT_HANDLER_METHOD;
                    if (!$reflection_class->has_native_method($method_name)) {
                        continue;
                    }
                    $method_reflection = $reflection_class->get_native_method($method_name);
                    foreach ($method_reflection->get_variants() as $variant) {
                        $return_types_map[$message_class_name][] = $variant->get_return_type();
                    }
                }
            }
        }
        $message_map = [];
        foreach ($return_types_map as $message_class_name => $return_types) {
            if (count($return_types) !== 1) {
                continue;
            }
            $message_map[$message_class_name] = $return_types[0];
        }
        return new Message_Map($message_map);
    }
    /** @return iterable<string, array<string, string>> */
    private function guess_handled_messages(Class_Reflection $reflection_class): iterable
    {
        if (interface_exists(Message_Subscriber_Interface::class) && $reflection_class->implements_interface(Message_Subscriber_Interface::class)) {
            $class_name = $reflection_class->get_name();
            foreach ($class_name::get_handled_messages() as $index => $value) {
                $contain_options = self::contain_options($index, $value);
                if ($contain_options === true) {
                    yield $index => $value;
                } elseif ($contain_options === false) {
                    yield $value => ['method' => self::DEFAULT_HANDLER_METHOD];
                }
            }
            return;
        }
        if (!$reflection_class->has_native_method(self::DEFAULT_HANDLER_METHOD)) {
            return;
        }
        $method_reflection = $reflection_class->get_native_method(self::DEFAULT_HANDLER_METHOD);
        $variants = $method_reflection->get_variants();
        if (count($variants) !== 1) {
            return;
        }
        $parameters = $variants[0]->get_parameters();
        if (count($parameters) !== 1) {
            return;
        }
        $class_names = $parameters[0]->get_type()->get_object_class_names();
        if (count($class_names) !== 1) {
            return;
        }
        yield $class_names[0] => ['method' => self::DEFAULT_HANDLER_METHOD];
    }
    /**
     * @param mixed $index
     * @param mixed $value
     * @phpstan-assert-if-true =class-string $index
     * @phpstan-assert-if-true =array<string, mixed> $value
     * @phpstan-assert-if-false =int $index
     * @phpstan-assert-if-false =class-string $value
     */
    private static function contain_options($index, $value): ?bool
    {
        if (is_string($index) && class_exists($index) && is_array($value)) {
            return true;
        }
        if (is_int($index) && is_string($value) && class_exists($value)) {
            return false;
        }
        return null;
    }
}
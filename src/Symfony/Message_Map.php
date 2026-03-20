<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Stan\Type\Type;
final class Message_Map
{
    /** @var array<string, Type> */
    private array $message_map;

    /**
     * @param array<string, Type> $message_map Map of message class name → PHPStan type of its handler's return
     */
    public function __construct(array $message_map)
    {
        $this->message_map = $message_map;
    }

    /**
     * Returns the PHPStan type associated with the given Messenger message class.
     *
     * Used to infer the return type of `MessageBusInterface::dispatch()` based on
     * the message class being dispatched. Returns null when the class has no
     * registered handler or is not known to the map.
     *
     * @param string $class Fully qualified class name of the Symfony Messenger message
     *
     * @return Type|null The PHPStan type of the handler's return value, or null if unknown
     */
    public function get_type_for_class(string $class): ?Type
    {
        return $this->message_map[$class] ?? null;
    }
}
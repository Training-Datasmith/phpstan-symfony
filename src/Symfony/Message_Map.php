<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Stan\Type\Type;
final class Message_Map
{
    /** @var array<string, Type> */
    private array $message_map;
    /** @param array<string, Type> $messageMap */
    public function __construct(array $message_map)
    {
        $this->message_map = $message_map;
    }
    public function get_type_for_class(string $class): ?Type
    {
        return $this->message_map[$class] ?? null;
    }
}
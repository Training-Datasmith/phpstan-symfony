<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

final class Service_Tag implements Service_Tag_Definition
{
    private string $name;
    /** @var array<string, string> */
    private array $attributes;
    /** @param array<string, string> $attributes */
    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }
    public function get_name(): string
    {
        return $this->name;
    }
    public function get_attributes(): array
    {
        return $this->attributes;
    }
}
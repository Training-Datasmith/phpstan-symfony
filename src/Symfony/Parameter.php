<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

final class Parameter implements Parameter_Definition
{
    private string $key;
    /** @var array<mixed>|bool|float|int|string */
    private $value;
    /**
     * @param array<mixed>|bool|float|int|string $value
     */
    public function __construct(string $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
    public function get_key(): string
    {
        return $this->key;
    }
    /**
     * @return array<mixed>|bool|float|int|string
     */
    public function get_value()
    {
        return $this->value;
    }
}
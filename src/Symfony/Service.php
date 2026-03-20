<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

final class Service implements Service_Definition
{
    private string $id;
    private ?string $class = null;
    private bool $public;
    private bool $synthetic;
    private ?string $alias = null;
    /** @var ServiceTag[] */
    private array $tags;
    /** @param ServiceTag[] $tags */
    public function __construct(string $id, ?string $class, bool $public, bool $synthetic, ?string $alias, array $tags = [])
    {
        $this->id = $id;
        $this->class = $class;
        $this->public = $public;
        $this->synthetic = $synthetic;
        $this->alias = $alias;
        $this->tags = $tags;
    }
    public function get_id(): string
    {
        return $this->id;
    }
    public function get_class(): ?string
    {
        return $this->class;
    }
    public function is_public(): bool
    {
        return $this->public;
    }
    public function is_synthetic(): bool
    {
        return $this->synthetic;
    }
    public function get_alias(): ?string
    {
        return $this->alias;
    }
    public function get_tags(): array
    {
        return $this->tags;
    }
}
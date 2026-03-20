<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function file_exists;
use function get_class;
use function is_readable;
use function method_exists;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Should_Not_Happen_Exception;
use Php_Stan\Type\Object_Type;
use function sprintf;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
final class Console_Application_Resolver
{
    private ?string $console_application_loader = null;
    private ?Application $console_application = null;
    public function __construct(?string $console_application_loader)
    {
        $this->console_application_loader = $console_application_loader;
    }
    public function has_console_application_loader(): bool
    {
        return $this->console_application_loader !== null;
    }
    private function get_console_application(): ?Application
    {
        if ($this->console_application_loader === null) {
            return null;
        }
        if ($this->console_application !== null) {
            return $this->console_application;
        }
        if (!file_exists($this->console_application_loader) || !is_readable($this->console_application_loader)) {
            throw new Should_Not_Happen_Exception(sprintf('Cannot load console application. Check the parameters.symfony.consoleApplicationLoader setting in PHPStan\'s config. The offending value is "%s".', $this->console_application_loader));
        }
        return $this->console_application = require $this->console_application_loader;
    }
    /**
     * @return Command[]
     */
    public function find_commands(Class_Reflection $class_reflection): array
    {
        $console_application = $this->get_console_application();
        if ($console_application === null) {
            return [];
        }
        $class_type = new Object_Type($class_reflection->get_name());
        if (!(new Object_Type(\Symfony\Component\Console\Command\Command::class))->is_super_type_of($class_type)->yes()) {
            return [];
        }
        $commands = [];
        foreach ($console_application->all() as $name => $command) {
            $command_class = new Object_Type(get_class($command));
            $is_lazy_command = (new Object_Type(\Symfony\Component\Console\Command\Lazy_Command::class))->is_super_type_of($command_class)->yes();
            if ($is_lazy_command && method_exists($command, 'getCommand')) {
                /** @var Command $wrappedCommand */
                $wrapped_command = $command->get_command();
                if (!$class_type->is_super_type_of(new Object_Type(get_class($wrapped_command)))->yes()) {
                    continue;
                }
            }
            if (!$is_lazy_command && !$class_type->is_super_type_of($command_class)->yes()) {
                continue;
            }
            $commands[$name] = $command;
        }
        return $commands;
    }
}
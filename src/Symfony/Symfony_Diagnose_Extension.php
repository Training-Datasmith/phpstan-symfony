<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Stan\Command\Output;
use Php_Stan\Diagnose\Diagnose_Extension;
use function sprintf;
class Symfony_Diagnose_Extension implements Diagnose_Extension
{
    private Console_Application_Resolver $console_application_resolver;
    public function __construct(Console_Application_Resolver $console_application_resolver)
    {
        $this->console_application_resolver = $console_application_resolver;
    }
    public function print(Output $output): void
    {
        $output->write_line_formatted(sprintf('<info>Symfony\'s consoleApplicationLoader:</info> %s', $this->console_application_resolver->has_console_application_loader() ? 'In use' : 'No'));
        $output->write_line_formatted('');
    }
}
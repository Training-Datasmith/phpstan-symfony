<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use function md5;
use Php_Parser\Node\Expr;
use Php_Parser\Pretty_Printer_Abstract;
use Php_Stan\Type\Type;
use Php_Stan\Type\Verbosity_Level;
use function sprintf;
final class Helper
{
    public static function create_marker_node(Expr $expr, Type $type, Pretty_Printer_Abstract $printer): \Php_Parser\Node\Expr\Variable
    {
        return new Expr\Variable(md5(sprintf('%s::%s', $printer->pretty_print_expr($expr), $type->describe(Verbosity_Level::precise()))));
    }
}
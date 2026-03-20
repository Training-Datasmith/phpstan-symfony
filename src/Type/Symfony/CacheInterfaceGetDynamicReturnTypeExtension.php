<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony;

use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Reflection\Method_Reflection;
use Php_Stan\Reflection\Parameters_Acceptor_Selector;
use Php_Stan\Type\Dynamic_Method_Return_Type_Extension;
use Php_Stan\Type\Generalize_Precision;
use Php_Stan\Type\Type;
final class Cache_Interface_Get_Dynamic_Return_Type_Extension implements Dynamic_Method_Return_Type_Extension
{
    public function get_class(): string
    {
        return 'Symfony\Contracts\Cache\CacheInterface';
    }
    public function is_method_supported(Method_Reflection $method_reflection): bool
    {
        return $method_reflection->get_name() === 'get';
    }
    public function get_type_from_method_call(Method_Reflection $method_reflection, Method_Call $method_call, Scope $scope): ?Type
    {
        if (!isset($method_call->get_args()[1])) {
            return null;
        }
        $callback_return_type = $scope->get_type($method_call->get_args()[1]->value);
        if ($callback_return_type->is_callable()->yes()) {
            $parameters_acceptor = Parameters_Acceptor_Selector::select_from_args($scope, $method_call->get_args(), $callback_return_type->get_callable_parameters_acceptors($scope));
            $return_type = $parameters_acceptor->get_return_type();
            // generalize template parameters
            return $return_type->generalize(Generalize_Precision::template_argument());
        }
        return null;
    }
}
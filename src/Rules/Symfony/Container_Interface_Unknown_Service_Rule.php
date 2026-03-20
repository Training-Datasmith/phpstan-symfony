<?php

declare (strict_types=1);
namespace Php_Stan\Rules\Symfony;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Node\Printer\Printer;
use Php_Stan\Rules\Rule;
use Php_Stan\Rules\Rule_Error_Builder;
use Php_Stan\Symfony\Service_Map;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Symfony\Helper;
use function sprintf;
/**
 * @implements Rule<MethodCall>
 */
final class Container_Interface_Unknown_Service_Rule implements Rule
{
    private Service_Map $service_map;
    private Printer $printer;
    public function __construct(Service_Map $symfony_service_map, Printer $printer)
    {
        $this->service_map = $symfony_service_map;
        $this->printer = $printer;
    }
    public function get_node_type(): string
    {
        return Method_Call::class;
    }
    public function process_node(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
        if ($node->name->name !== 'get' || !isset($node->get_args()[0])) {
            return [];
        }
        $arg_type = $scope->get_type($node->var);
        $is_container_bag_type = (new Object_Type('Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface'))->is_super_type_of($arg_type);
        if ($is_container_bag_type->yes()) {
            return [];
        }
        $is_controller_type = (new Object_Type('Symfony\Bundle\FrameworkBundle\Controller\Controller'))->is_super_type_of($arg_type);
        $is_abstract_controller_type = (new Object_Type('Symfony\Bundle\FrameworkBundle\Controller\AbstractController'))->is_super_type_of($arg_type);
        $is_container_type = (new Object_Type('Symfony\Component\DependencyInjection\ContainerInterface'))->is_super_type_of($arg_type);
        $is_psr_container_type = (new Object_Type(\Psr\Container\Container_Interface::class))->is_super_type_of($arg_type);
        if (!$is_controller_type->yes() && !$is_abstract_controller_type->yes() && !$is_container_type->yes() && !$is_psr_container_type->yes()) {
            return [];
        }
        $service_id = $this->service_map::get_service_id_from_node($node->get_args()[0]->value, $scope);
        if ($service_id !== null) {
            $service = $this->service_map->get_service($service_id);
            $service_id_type = $scope->get_type($node->get_args()[0]->value);
            if ($service === null && !$scope->get_type(Helper::create_marker_node($node->var, $service_id_type, $this->printer))->equals($service_id_type)) {
                return [Rule_Error_Builder::message(sprintf('Service "%s" is not registered in the container.', $service_id))->identifier('symfonyContainer.serviceNotFound')->build()];
            }
        }
        return [];
    }
}
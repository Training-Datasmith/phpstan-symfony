<?php

declare (strict_types=1);
namespace Php_Stan\Rules\Symfony;

use Php_Parser\Node;
use Php_Parser\Node\Expr\Method_Call;
use Php_Stan\Analyser\Scope;
use Php_Stan\Rules\Rule;
use Php_Stan\Rules\Rule_Error_Builder;
use Php_Stan\Symfony\Service_Map;
use Php_Stan\Trinary_Logic;
use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use function sprintf;
/**
 * @implements Rule<MethodCall>
 */
final class Container_Interface_Private_Service_Rule implements Rule
{
    private Service_Map $service_map;
    public function __construct(Service_Map $symfony_service_map)
    {
        $this->service_map = $symfony_service_map;
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
        $is_test_container = $this->is_test_container($arg_type, $scope);
        $is_old_service_subscriber = (new Object_Type('Symfony\Component\DependencyInjection\ServiceSubscriberInterface'))->is_super_type_of($arg_type);
        $is_service_subscriber = $this->is_service_subscriber($arg_type, $scope);
        $is_service_locator = (new Object_Type('Symfony\Component\DependencyInjection\ServiceLocator'))->is_super_type_of($arg_type);
        if ($is_test_container->yes() || $is_old_service_subscriber->yes() || $is_service_subscriber->yes() || $is_service_locator->yes()) {
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
            if ($service !== null && !$service->is_public()) {
                return [Rule_Error_Builder::message(sprintf('Service "%s" is private.', $service_id))->identifier('symfonyContainer.privateService')->build()];
            }
        }
        return [];
    }
    private function is_service_subscriber(Type $container_type, Scope $scope): Trinary_Logic
    {
        $service_subscriber_interface_type = new Object_Type(\Symfony\Contracts\Service\Service_Subscriber_Interface::class);
        $is_container_service_subscriber = $service_subscriber_interface_type->is_super_type_of($container_type)->result;
        $class_reflection = $scope->get_class_reflection();
        if ($class_reflection === null) {
            return $is_container_service_subscriber;
        }
        $contained_class_type = new Object_Type($class_reflection->get_name());
        return $is_container_service_subscriber->or($service_subscriber_interface_type->is_super_type_of($contained_class_type)->result);
    }
    private function is_test_container(Type $container_type, Scope $scope): Trinary_Logic
    {
        $test_container = new Object_Type('Symfony\Bundle\FrameworkBundle\Test\TestContainer');
        $is_test_container = $test_container->is_super_type_of($container_type)->result;
        $class_reflection = $scope->get_class_reflection();
        if ($class_reflection === null) {
            return $is_test_container;
        }
        $container_interface = new Object_Type('Symfony\Component\DependencyInjection\ContainerInterface');
        $kernel_test_case = new Object_Type('Symfony\Bundle\FrameworkBundle\Test\KernelTestCase');
        $contained_class_type = new Object_Type($class_reflection->get_name());
        return $is_test_container->or($container_interface->is_super_type_of($container_type)->result->and($kernel_test_case->is_super_type_of($contained_class_type)->result));
    }
}
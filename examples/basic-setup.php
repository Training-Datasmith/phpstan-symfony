<?php

declare(strict_types=1);

/**
 * Example: Setting up phpstan-symfony for a Symfony application.
 *
 * Install:
 *   composer require --dev phpstan/phpstan-symfony
 *
 * The extension is auto-loaded via phpstan/extension-installer.
 * For container-aware analysis, configure phpstan.neon:
 *
 *   parameters:
 *     symfony:
 *       containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
 *       # Optional: enables console argument/option type inference
 *       consoleApplicationLoader: tests/console-application-loader.php
 *
 * Run `bin/console cache:warmup` before PHPStan to ensure the container XML exists.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    public function list(Request $request): JsonResponse
    {
        // With phpstan-symfony, PHPStan knows getContent() returns string|false (not mixed).
        $body = $request->getContent();

        // PHPStan knows InputBag::get() returns string|null (not mixed).
        $filter = $request->query->get('filter');

        // Container get() returns the actual service type based on container XML.
        // Without container XML, returns object.
        // PHPStan will also error if the service ID is private or unknown.
        /** @var \App\Repository\UserRepository $repo */
        $repo = $this->container->get(\App\Repository\UserRepository::class);

        return $this->json([
            'filter' => $filter,
            'count' => count($repo->findAll()),
        ]);
    }
}

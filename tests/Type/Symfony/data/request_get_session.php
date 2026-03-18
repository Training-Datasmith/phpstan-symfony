<?php

declare(strict_types=1);

use function PHPStan\Testing\assertType;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/** @var \Symfony\Component\HttpFoundation\Request $request */
$request = doRequest();

$session1 = $request->getSession();
assertType(SessionInterface::class, $request->getSession());

if ($request->hasSession()) {
    assertType(SessionInterface::class, $request->getSession());
}

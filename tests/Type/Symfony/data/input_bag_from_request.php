<?php

declare(strict_types=1);

namespace InputBag;

use function PHPStan\Testing\assertType;

use Symfony\Component\HttpFoundation\Request;

class Foo
{
    public function doFoo(Request $request): void
    {
        assertType('bool|float|int|string|null', $request->request->get('foo'));
        assertType('string|null', $request->query->get('foo'));
        assertType('string|null', $request->cookies->get('foo'));

        assertType('bool|float|int|string', $request->request->get('foo', 'foo'));
        assertType('string', $request->query->get('foo', 'foo'));
        assertType('string', $request->cookies->get('foo', 'foo'));
    }

}

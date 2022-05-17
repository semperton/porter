<?php

declare(strict_types=1);

namespace Semperton\Porter;

use ArrayIterator;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Semperton\Porter\RequestHandler;
use SplQueue;

final class HandlerTest extends TestCase
{
	/** @var ServerRequestInterface */
	protected $request;

	/** @var callable */
	protected $responder;

	protected function setUp(): void
	{
		$this->request = new ServerRequest('GET', 'https://www.example.net');
		$this->responder = function ($request, $handler) {
			$response = new Response();
			$response->getBody()->write('R');
			return $response;
		};

		MockMiddleware::$count = 0;
	}

	public function testEmptyMiddlewareStack(): void
	{
		$this->expectException(OutOfBoundsException::class);
		$this->expectExceptionMessage('End of middleware stack, no response was returned');

		$handler = new RequestHandler(new SplQueue());

		$handler->handle($this->request);
	}

	public function testMiddlewareResolver(): void
	{
		$resolver = new MiddlewareResolver();

		$middleware = new ArrayIterator([
			new MockMiddleware,
			MockMiddleware::class,
			MockMiddleware::class,
			$this->responder
		]);
		$handler = new RequestHandler($middleware, $resolver);

		$response = $handler->handle($this->request);

		$this->assertEquals('R-3-2-1', (string)$response->getBody());
	}

	public function testInvalidMiddleware(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to resolve middleware, < string > is not a valid Middleware');

		$middleware = new ArrayIterator([
			'SomeMiddleware',
		]);
		$handler = new RequestHandler($middleware);

		$handler->handle($this->request);
	}

	public function testSubHandler(): void
	{
		$resolver = new MiddlewareResolver();

		$middleware = new ArrayIterator([
			new MockMiddleware,
			new RequestHandler(new ArrayIterator([
				MockMiddleware::class,
				MockMiddleware::class
			]), $resolver),
			MockMiddleware::class,
			$this->responder
		]);
		$handler = new RequestHandler($middleware, $resolver);

		$response = $handler->handle($this->request);

		$this->assertEquals('R-4-3-2-1', (string)$response->getBody());
	}
}

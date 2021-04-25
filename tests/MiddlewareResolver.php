<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class MiddlewareResolver
{
	public function __invoke($middleware): MiddlewareInterface
	{
		if (is_string($middleware)) {
			return new $middleware();
		}

		if (is_callable($middleware)) {
			return new class($middleware) implements MiddlewareInterface
			{
				protected $callable;
				public function __construct(callable $callable)
				{
					$this->callable = $callable;
				}
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
				{
					return ($this->callable)($request, $handler);
				}
			};
		}

		throw new RuntimeException('Unable to resolve middleware');
	}
}

<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MockMiddleware implements MiddlewareInterface
{
	/** @var int */
	public static $count = 0;

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$count = ++self::$count;

		$response = $handler->handle($request);
		$response->getBody()->write('-' . (string)$count);
		return $response;
	}
}

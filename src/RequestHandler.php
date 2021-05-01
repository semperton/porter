<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use OutOfBoundsException;
use Iterator;
use RuntimeException;

final class RequestHandler implements RequestHandlerInterface, MiddlewareInterface
{
	/** @var Iterator */
	protected $middleware;

	/** @var null|callable */
	protected $resolver;

	public function __construct(Iterator $middleware, ?callable $resolver = null)
	{
		$this->middleware = $middleware;
		$this->resolver = $resolver;

		$this->middleware->rewind();
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if (!$this->middleware->valid()) {
			throw new OutOfBoundsException('End of middleware stack, no response was returned');
		}

		$middleware = $this->middleware->current();

		if (!($middleware instanceof MiddlewareInterface)) {

			if ($this->resolver !== null) {
				/** @var object */
				$middleware = ($this->resolver)($middleware);
			}

			if (!($middleware instanceof MiddlewareInterface)) {
				$type = gettype($middleware);
				throw new RuntimeException("Type < $type > is not a valid MiddlewareInterface");
			}
		}

		$this->middleware->next();

		return $middleware->process($request, $this);
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->middleware->valid()) {

			return $this->handle($request);
		}

		return $handler->handle($request);
	}
}

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

use function is_callable;
use function gettype;

final class RequestHandler implements RequestHandlerInterface, MiddlewareInterface
{
	/** @var Iterator */
	protected $middleware;

	/** @var callable */
	protected $resolver;

	public function __construct(Iterator $middleware, ?callable $resolver = null)
	{
		$this->middleware = $middleware;
		$this->resolver = $resolver ?? [$this, 'resolve'];

		$this->middleware->rewind();
	}

	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		if (!$this->middleware->valid()) {
			throw new OutOfBoundsException('End of middleware stack, no response was returned');
		}

		/** @var mixed */
		$middleware = $this->middleware->current();

		$this->middleware->next();

		if (
			!($middleware instanceof MiddlewareInterface) &&
			!($middleware instanceof RequestHandlerInterface) &&
			!is_callable($middleware)
		) {

			/** @var mixed */
			$middleware = ($this->resolver)($middleware);
		}

		if ($middleware instanceof MiddlewareInterface) {

			return $middleware->process($request, $this);
		}

		if ($middleware instanceof RequestHandlerInterface) {

			return $middleware->handle($request);
		}

		if (is_callable($middleware)) {

			/** @var ResponseInterface */
			return $middleware($request, $this);
		}

		$type = gettype($middleware);
		throw new RuntimeException("Unable to resolve middleware, < $type > is not a valid Middleware");
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $this->handle($request);
		} catch (OutOfBoundsException $e) {
		}

		return $handler->handle($request);
	}

	/**
	 * @param mixed $middleware
	 * @return mixed
	 */
	protected function resolve($middleware)
	{
		return $middleware;
	}
}

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

		$middleware = $this->middleware->current();

		if (!($middleware instanceof MiddlewareInterface)) {

			/** @var mixed */
			$middleware = ($this->resolver)($middleware);

			if (!($middleware instanceof MiddlewareInterface)) {
				$type = gettype($middleware);
				throw new RuntimeException("Unable to resolve middleware, < $type > is not a valid MiddlewareInterface");
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

	/**
	 * @param mixed $middleware
	 * @return mixed
	 */
	protected function resolve($middleware)
	{
		if (is_callable($middleware)) {
			return $middleware();
		}

		if (is_string($middleware) && class_exists($middleware)) {
			/** @psalm-suppress MixedMethodCall */
			return new $middleware();
		}

		return null;
	}
}

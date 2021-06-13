<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Semperton\Porter\Emitter\EmitterInterface;
use Throwable;

final class RequestRunner
{
	/** @var RequestHandlerInterface */
	protected $requestHandler;

	/** @var EmitterInterface */
	protected $responseEmitter;

	/** @var callable */
	protected $serverRequestGenerator;

	/** @var callable|null */
	protected $errorHandler;

	public function __construct(
		RequestHandlerInterface $requestHandler,
		EmitterInterface $responseEmitter,
		callable $serverRequestGenerator,
		?callable $errorHandler = null
	) {
		$this->requestHandler = $requestHandler;
		$this->responseEmitter = $responseEmitter;
		$this->serverRequestGenerator = $serverRequestGenerator;
		$this->errorHandler = $errorHandler;
	}

	public function run(): void
	{
		try {
			/** @var ServerRequestInterface */
			$request = ($this->serverRequestGenerator)();
			$response = $this->requestHandler->handle($request);

			$this->responseEmitter->emit($response);
		} catch (Throwable $exception) {

			if ($this->errorHandler === null) {
				throw new RuntimeException('Unable to generate response for exception', 0, $exception);
			}

			/** @var ResponseInterface */
			$response = ($this->errorHandler)($exception);

			$this->responseEmitter->emit($response);
		}
	}
}

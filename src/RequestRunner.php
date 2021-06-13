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
	/** @var callable */
	protected $serverRequestCreator;

	/** @var RequestHandlerInterface */
	protected $requestHandler;

	/** @var EmitterInterface */
	protected $responseEmitter;

	/** @var callable|null */
	protected $errorHandler;

	public function __construct(
		callable $serverRequestCreator,
		RequestHandlerInterface $requestHandler,
		EmitterInterface $responseEmitter,
		?callable $errorHandler = null
	) {
		$this->serverRequestCreator = $serverRequestCreator;
		$this->requestHandler = $requestHandler;
		$this->responseEmitter = $responseEmitter;
		$this->errorHandler = $errorHandler;
	}

	public function run(): void
	{
		try {
			/** @var ServerRequestInterface */
			$request = ($this->serverRequestCreator)();
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

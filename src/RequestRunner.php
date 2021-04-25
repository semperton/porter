<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Semperton\Porter\Emitter\EmitterInterface;

final class RequestRunner
{
	/** @var RequestHandlerInterface */
	protected $requestHandler;

	/** @var EmitterInterface */
	protected $responseEmitter;

	public function __construct(RequestHandlerInterface $requestHandler, EmitterInterface $responseEmitter)
	{
		$this->requestHandler = $requestHandler;
		$this->responseEmitter = $responseEmitter;
	}

	public function run(ServerRequestInterface $request): void
	{
		$response = $this->requestHandler->handle($request);

		$this->responseEmitter->emit($response);
	}
}

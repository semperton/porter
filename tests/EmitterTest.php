<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Semperton\Porter\Emitter\EmitterException;
use Semperton\Porter\Emitter\SapiEmitter;

final class EmitterTest extends TestCase
{
	protected function newResponse(): ResponseInterface
	{
		return new Response();
	}

	public function testHeadersAlreadySent(): void
	{
		$this->expectException(EmitterException::class);
		$this->expectExceptionMessage('Headers already sent, unable to emit response');

		$response = $this->newResponse();
		$emitter = new SapiEmitter();

		$emitter->emit($response);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOutputAlreadyStarted(): void
	{
		$this->expectException(EmitterException::class);
		$this->expectExceptionMessage('Output already startet, unable to emit response');

		echo '0';

		$response = $this->newResponse();
		$emitter = new SapiEmitter();

		$emitter->emit($response);
	}
}

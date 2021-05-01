<?php

declare(strict_types=1);

namespace Semperton\Porter;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Semperton\Porter\Emitter\SapiEmitter;
use RuntimeException;

final class EmitterTest extends TestCase
{
	protected function newResponse(): ResponseInterface
	{
		return new Response();
	}

	public function testHeadersAlreadySent(): void
	{
		$this->expectException(RuntimeException::class);
		// Headers already sent in < \vendor\phpunit\phpunit\src\Util\Printer.php > on line < 104 >, unable to emit response

		$response = $this->newResponse();
		$emitter = new SapiEmitter();

		$emitter->emit($response);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOutputAlreadyStarted(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Output already startet, unable to emit response');

		echo '0';

		$response = $this->newResponse();
		$emitter = new SapiEmitter();

		$emitter->emit($response);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testEmitResponse(): void
	{
		$response = $this->newResponse();
		$emitter = new SapiEmitter();

		$message = 'Hello World';
		$response->getBody()->write($message);

		ob_start();

		$emitter->emit($response);

		$contents = ob_get_clean();

		$this->assertEquals($message, $contents);
	}
}

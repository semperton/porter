<?php

declare(strict_types=1);

namespace Semperton\Porter\Emitter;

use Psr\Http\Message\ResponseInterface;
use const CONNECTION_NORMAL;

final class SapiEmitter implements EmitterInterface
{
	/** @var int */
	protected $chunkSize;

	public function __construct(int $chunkSize = 1024 * 4)
	{
		$this->chunkSize = $chunkSize;
	}

	public function emit(ResponseInterface $response): void
	{
		if (headers_sent()) {
			throw new EmitterException('Headers already sent, unable to emit response');
		}

		if (ob_get_level() > 0 && ob_get_length() > 0) {
			throw new EmitterException('Output already startet, unable to emit response');
		}

		$responseEmpty = $this->isResponseEmpty($response);

		$this->sendStatusLine($response);
		$this->sendHeaders($response);

		if (!$responseEmpty) {
			$this->sendBody($response);
		}
	}

	protected function sendStatusLine(ResponseInterface $response): void
	{
		$protocol = $response->getProtocolVersion();
		$status = $response->getStatusCode();
		$reason = $response->getReasonPhrase();

		header("HTTP/$protocol $status $reason", true, $status);
	}

	protected function sendHeaders(ResponseInterface $response): void
	{
		$headers = $response->getHeaders();

		foreach ($headers as $name => $values) {

			$first = strtolower((string)$name) !== 'set-cookie';

			foreach ($values as $value) {
				header("$name: $value", $first);
				$first = false;
			}
		}
	}

	protected function sendBody(ResponseInterface $response): void
	{
		$stream = $response->getBody();

		if ($this->chunkSize < 1) {
			echo (string)$stream;
			return;
		}

		if ($stream->isSeekable()) {
			$stream->rewind();
		}

		while (!$stream->eof()) {

			echo $stream->read($this->chunkSize);

			if (connection_status() !== CONNECTION_NORMAL) {
				break;
			}
		}
	}

	protected function isResponseEmpty(ResponseInterface $response): bool
	{
		$statusCode = $response->getStatusCode();
		if (in_array($statusCode, [204, 205, 304])) {
			return true;
		}

		return !$response->getBody()->isReadable();
	}
}

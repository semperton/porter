<?php

declare(strict_types=1);

namespace Semperton\Porter\Emitter;

use Psr\Http\Message\ResponseInterface;

interface EmitterInterface
{
	public function emit(ResponseInterface $response): void;
}

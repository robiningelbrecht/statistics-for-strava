<?php

namespace App\Infrastructure\Http;

use App\Infrastructure\Config\PlatformEnvironment;
use Symfony\Component\HttpFoundation\JsonResponse;

class JsonErrorResponse extends JsonResponse
{
    /**
     * @param array<mixed> $data
     */
    public function __construct(array $data, int $status)
    {
        parent::__construct($data, $status);
    }

    public static function fromThrowableAndEnvironment(\Throwable $exception, HttpStatusCode $status, PlatformEnvironment $environment): self
    {
        $data = [
            'message' => $exception->getMessage(),
        ];
        if (PlatformEnvironment::DEV === $environment) {
            $data['exception'] = $exception::class;
            $data['file'] = $exception->getFile();
            $data['line'] = $exception->getLine();
            $data['trace'] = $exception->getTrace();
        }

        return new self($data, $status->value);
    }
}

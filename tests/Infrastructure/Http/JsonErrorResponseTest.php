<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Config\PlatformEnvironment;
use App\Infrastructure\Http\HttpStatusCode;
use App\Infrastructure\Http\JsonErrorResponse;
use App\Infrastructure\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class JsonErrorResponseTest extends TestCase
{
    use MatchesSnapshots;

    public function testFromThrowableAndEnvironment(): void
    {
        $this->assertMatchesJsonSnapshot(
            JsonErrorResponse::fromThrowableAndEnvironment(new \RuntimeException('Some message'), HttpStatusCode::INTERNAL_SERVER_ERROR, PlatformEnvironment::PROD)->getContent()
        );

        // Because stack traces contain random seeds, we cannot use snapshots to assert them.
        // We'll just make sure the expected keys are in the response.
        $response = JsonErrorResponse::fromThrowableAndEnvironment(new \RuntimeException('Some message'), HttpStatusCode::INTERNAL_SERVER_ERROR, PlatformEnvironment::DEV);
        $content = Json::decode($response->getContent());
        $this->assertArrayHasKey('exception', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('file', $content);
        $this->assertArrayHasKey('line', $content);
        $this->assertArrayHasKey('trace', $content);
    }
}

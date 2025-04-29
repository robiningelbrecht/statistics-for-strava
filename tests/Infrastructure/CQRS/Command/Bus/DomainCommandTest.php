<?php

namespace App\Tests\Infrastructure\CQRS\Command\Bus;

use App\Infrastructure\Serialization\Json;
use App\Tests\Infrastructure\CQRS\Command\Bus\RunAnOperation\RunAnOperation;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;

class DomainCommandTest extends TestCase
{
    use MatchesSnapshots;

    public function testItShouldJsonSerialize(): void
    {
        $this->assertMatchesJsonSnapshot(Json::encode(new RunAnOperation('string')));
    }
}

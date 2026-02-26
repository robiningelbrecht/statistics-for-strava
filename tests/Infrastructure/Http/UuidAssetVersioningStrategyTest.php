<?php

namespace App\Tests\Infrastructure\Http;

use App\Infrastructure\Http\UuidAssetVersioningStrategy;
use App\Tests\Infrastructure\ValueObject\Identifier\FakeUuidFactory;
use PHPUnit\Framework\TestCase;

class UuidAssetVersioningStrategyTest extends TestCase
{
    public function testApplyVersion(): void
    {
        $strategy = new UuidAssetVersioningStrategy(new FakeUuidFactory());

        $this->assertEquals(
            '/test/file?0025176c-5652-11ee-923d-02424dd627d5',
            $strategy->applyVersion('/test/file')
        );

        $this->assertEquals(
            'test/file?0025176c-5652-11ee-923d-02424dd627d5',
            $strategy->applyVersion('test/file')
        );
    }
}

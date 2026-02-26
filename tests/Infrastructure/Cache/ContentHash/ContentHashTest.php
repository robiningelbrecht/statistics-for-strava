<?php

namespace App\Tests\Infrastructure\Cache\ContentHash;

use App\Infrastructure\Cache\ContentHash\ContentHash;
use PHPUnit\Framework\TestCase;

class ContentHashTest extends TestCase
{
    public function testComputeUsessha1(): void
    {
        $contentHash = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'some content',
        );

        $this->assertEquals(
            expected: sha1('some content'),
            actual: $contentHash->getHash(),
        );
    }

    public function testComputeDifferentContentProducesDifferentHash(): void
    {
        $hashA = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'content A',
        );
        $hashB = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'content B',
        );

        $this->assertNotEquals(
            expected: $hashA->getHash(),
            actual: $hashB->getHash(),
        );
    }

    public function testEqualsSameContent(): void
    {
        $hashA = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'same content',
        );
        $hashB = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'same content',
        );

        $this->assertTrue($hashA->equals($hashB));
    }

    public function testEqualsDifferentContent(): void
    {
        $hashA = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'content A',
        );
        $hashB = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'content B',
        );

        $this->assertFalse($hashA->equals($hashB));
    }

    public function testEqualsIgnoresEntityTypeAndId(): void
    {
        $hashA = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'same content',
        );
        $hashB = ContentHash::compute(
            entityType: 'segment',
            entityId: '999',
            content: 'same content',
        );

        $this->assertTrue($hashA->equals($hashB));
    }

    public function testEqualsFromStateAndCompute(): void
    {
        $computed = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'some content',
        );
        $fromState = ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: sha1('some content'),
        );

        $this->assertTrue($computed->equals($fromState));
    }

    public function testEqualsFromStateWithDifferentHash(): void
    {
        $computed = ContentHash::compute(
            entityType: 'activity',
            entityId: '100',
            content: 'some content',
        );
        $fromState = ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'completely-different-hash',
        );

        $this->assertFalse($computed->equals($fromState));
    }
}

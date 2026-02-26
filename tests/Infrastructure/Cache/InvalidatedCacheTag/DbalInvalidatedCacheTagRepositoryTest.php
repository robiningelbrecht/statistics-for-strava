<?php

namespace App\Tests\Infrastructure\Cache\InvalidatedCacheTag;

use App\Infrastructure\Cache\InvalidatedCacheTag\DbalInvalidatedCacheTagRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\InvalidatedCacheTagRepository;
use App\Infrastructure\Cache\Tag;
use App\Tests\ContainerTestCase;

class DbalInvalidatedCacheTagRepositoryTest extends ContainerTestCase
{
    private InvalidatedCacheTagRepository $invalidatedCacheTagCache;

    public function testInvalidateSingleTag(): void
    {
        $this->invalidatedCacheTagCache->invalidate(Tag::activity('123'));

        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('segment:'));
    }

    public function testInvalidateMultipleTags(): void
    {
        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('1'),
            Tag::activity('2'),
            Tag::segment('10'),
        );

        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('segment:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('challenges'));
    }

    public function testInvalidateWithNoTagsDoesNotFail(): void
    {
        $this->invalidatedCacheTagCache->invalidate();

        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
    }

    public function testInvalidateDuplicateTagIsIdempotent(): void
    {
        $this->invalidatedCacheTagCache->invalidate(Tag::activity('123'));
        $this->invalidatedCacheTagCache->invalidate(Tag::activity('123'));

        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
    }

    public function testHasAnyWithPrefixReturnsFalseWhenEmpty(): void
    {
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('segment:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('challenges'));
    }

    public function testHasAnyWithPrefixMatchesExactTag(): void
    {
        $this->invalidatedCacheTagCache->invalidate(Tag::challenges());

        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('challenges'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('challenge:'));
    }

    public function testHasAnyWithPrefixMatchesPartialPrefix(): void
    {
        $this->invalidatedCacheTagCache->invalidate(Tag::bestEffort(
            distanceInMeter: 1000,
            sportType: 'Run',
        ));

        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('best-effort:'));
        $this->assertTrue($this->invalidatedCacheTagCache->hasAnyWithPrefix('best-effort:1000:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('best-effort:2000:'));
    }

    public function testClearAllRemovesAllTags(): void
    {
        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('1'),
            Tag::segment('10'),
            Tag::challenges(),
        );

        $this->invalidatedCacheTagCache->clearAll();

        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('segment:'));
        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('challenges'));
    }

    public function testClearAllOnEmptyTableDoesNotFail(): void
    {
        $this->invalidatedCacheTagCache->clearAll();

        $this->assertFalse($this->invalidatedCacheTagCache->hasAnyWithPrefix('activity:'));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->invalidatedCacheTagCache = new DbalInvalidatedCacheTagRepository(
            $this->getConnection()
        );
    }
}

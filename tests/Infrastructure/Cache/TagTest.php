<?php

namespace App\Tests\Infrastructure\Cache;

use App\Infrastructure\Cache\Tag;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testActivity(): void
    {
        $this->assertEquals(
            expected: 'activity:123',
            actual: (string) Tag::activity('123'),
        );
    }

    public function testActivityWithDifferentIds(): void
    {
        $this->assertNotEquals(
            expected: (string) Tag::activity('1'),
            actual: (string) Tag::activity('2'),
        );
    }

    public function testSegment(): void
    {
        $this->assertEquals(
            expected: 'segment:456',
            actual: (string) Tag::segment('456'),
        );
    }

    public function testSegmentWithDifferentIds(): void
    {
        $this->assertNotEquals(
            expected: (string) Tag::segment('1'),
            actual: (string) Tag::segment('2'),
        );
    }

    public function testBestEffort(): void
    {
        $this->assertEquals(
            expected: 'best-effort:1000:Run',
            actual: (string) Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        );
    }

    public function testBestEffortDifferentDistances(): void
    {
        $this->assertNotEquals(
            expected: (string) Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
            actual: (string) Tag::bestEffort(
                distanceInMeter: 5000,
                sportType: 'Run',
            ),
        );
    }

    public function testBestEffortDifferentSportTypes(): void
    {
        $this->assertNotEquals(
            expected: (string) Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
            actual: (string) Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Ride',
            ),
        );
    }

    public function testChallenges(): void
    {
        $this->assertEquals(
            expected: 'challenges',
            actual: (string) Tag::challenges(),
        );
    }

    public function testGear(): void
    {
        $this->assertEquals(
            expected: 'gear',
            actual: (string) Tag::gear(),
        );
    }

    public function testAthlete(): void
    {
        $this->assertEquals(
            expected: 'athlete',
            actual: (string) Tag::athlete(),
        );
    }

    public function testSameFactoryAndArgumentsProduceEqualTags(): void
    {
        $this->assertEquals(
            expected: Tag::activity('100'),
            actual: Tag::activity('100'),
        );
    }

    public function testDifferentTagTypesAreNotEqual(): void
    {
        $this->assertNotEquals(
            expected: (string) Tag::activity('100'),
            actual: (string) Tag::segment('100'),
        );
    }

    #[DataProvider('provideAllTagTypes')]
    public function testStringableInterface(Tag $tag, string $expectedString): void
    {
        $this->assertSame(
            expected: $expectedString,
            actual: (string) $tag,
        );
    }

    /**
     * @return \Generator<string, array{Tag, string}>
     */
    public static function provideAllTagTypes(): \Generator
    {
        yield 'activity' => [Tag::activity('42'), 'activity:42'];
        yield 'segment' => [Tag::segment('7'), 'segment:7'];
        yield 'best-effort' => [Tag::bestEffort(
            distanceInMeter: 400,
            sportType: 'Swim',
        ), 'best-effort:400:Swim'];
        yield 'challenges' => [Tag::challenges(), 'challenges'];
        yield 'gear' => [Tag::gear(), 'gear'];
        yield 'athlete' => [Tag::athlete(), 'athlete'];
    }
}

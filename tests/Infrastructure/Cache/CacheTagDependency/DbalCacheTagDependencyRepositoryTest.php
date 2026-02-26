<?php

namespace App\Tests\Infrastructure\Cache\CacheTagDependency;

use App\Infrastructure\Cache\CacheTagDependency\CacheTagDependency;
use App\Infrastructure\Cache\CacheTagDependency\CacheTagDependencyRepository;
use App\Infrastructure\Cache\CacheTagDependency\DbalCacheTagDependencyRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\DbalInvalidatedCacheTagRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\InvalidatedCacheTagRepository;
use App\Infrastructure\Cache\Tag;
use App\Tests\ContainerTestCase;

class DbalCacheTagDependencyRepositoryTest extends ContainerTestCase
{
    private CacheTagDependencyRepository $cacheTagDependencyCache;
    private InvalidatedCacheTagRepository $invalidatedCacheTagCache;

    public function testRegisterAndFindDirtyEntities(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: Tag::activity('200'),
        ));

        $this->invalidatedCacheTagCache->invalidate(Tag::activity('100'));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEqualsCanonicalizing(
            expected: ['100'],
            actual: $dirtyIds,
        );
    }

    public function testFindReturnsMultipleDirtyEntities(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: Tag::activity('200'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '300',
            dependsOnTag: Tag::activity('300'),
        ));

        $this->invalidatedCacheTagCache->invalidate(Tag::bestEffort(
            distanceInMeter: 1000,
            sportType: 'Run',
        ));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEqualsCanonicalizing(
            expected: ['100', '200'],
            actual: $dirtyIds,
        );
    }

    public function testFindReturnsDistinctEntityIds(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));

        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('100'),
            Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        );

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEqualsCanonicalizing(
            expected: ['100'],
            actual: $dirtyIds,
        );
    }

    public function testFindFiltersOnEntityType(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'segment',
            entityId: '50',
            dependsOnTag: Tag::segment('50'),
        ));

        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('100'),
            Tag::segment('50'),
        );

        $this->assertEqualsCanonicalizing(
            expected: ['100'],
            actual: $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity'),
        );
        $this->assertEqualsCanonicalizing(
            expected: ['50'],
            actual: $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('segment'),
        );
    }

    public function testFindReturnsEmptyWhenNothingInvalidated(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testFindReturnsEmptyWhenNoDependenciesRegistered(): void
    {
        $this->invalidatedCacheTagCache->invalidate(Tag::activity('100'));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testClearForEntityThenRegisterReplacesPreviousDependencies(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));

        $this->invalidatedCacheTagCache->invalidate(Tag::bestEffort(
            distanceInMeter: 1000,
            sportType: 'Run',
        ));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testClearForEntityRemovesDependencies(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: Tag::activity('200'),
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('100'),
            Tag::activity('200'),
        );

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEqualsCanonicalizing(
            expected: ['200'],
            actual: $dirtyIds,
        );
    }

    public function testClearForEntityDoesNotAffectOtherEntityTypes(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: Tag::activity('100'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'segment',
            entityId: '100',
            dependsOnTag: Tag::segment('100'),
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->invalidatedCacheTagCache->invalidate(Tag::segment('100'));

        $this->assertEmpty(
            $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity')
        );
        $this->assertEqualsCanonicalizing(
            expected: ['100'],
            actual: $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('segment'),
        );
    }

    public function testBestEffortCrossEntityDependency(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'A',
            dependsOnTag: Tag::activity('A'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'A',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'B',
            dependsOnTag: Tag::activity('B'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'B',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'C',
            dependsOnTag: Tag::activity('C'),
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'C',
            dependsOnTag: Tag::bestEffort(
                distanceInMeter: 5000,
                sportType: 'Run',
            ),
        ));

        $this->invalidatedCacheTagCache->invalidate(
            Tag::activity('B'),
            Tag::bestEffort(
                distanceInMeter: 1000,
                sportType: 'Run',
            ),
        );

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEqualsCanonicalizing(
            expected: ['A', 'B'],
            actual: $dirtyIds,
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $this->cacheTagDependencyCache = new DbalCacheTagDependencyRepository($connection);
        $this->invalidatedCacheTagCache = new DbalInvalidatedCacheTagRepository($connection);
    }
}

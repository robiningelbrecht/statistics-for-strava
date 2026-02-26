<?php

namespace App\Tests\Infrastructure\Cache\CacheTagDependency;

use App\Infrastructure\Cache\CacheTagDependency\CacheTagDependency;
use App\Infrastructure\Cache\CacheTagDependency\CacheTagDependencyRepository;
use App\Infrastructure\Cache\CacheTagDependency\DbalCacheTagDependencyRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\DbalInvalidatedCacheTagRepository;
use App\Infrastructure\Cache\InvalidatedCacheTag\InvalidatedCacheTagRepository;
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
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: 'activity:200',
        ));

        $this->invalidatedCacheTagCache->invalidate('activity:100');

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
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'best-effort:1000:Run',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: 'activity:200',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: 'best-effort:1000:Run',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '300',
            dependsOnTag: 'activity:300',
        ));

        $this->invalidatedCacheTagCache->invalidate('best-effort:1000:Run');

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
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'best-effort:1000:Run',
        ));

        $this->invalidatedCacheTagCache->invalidate(
            'activity:100',
            'best-effort:1000:Run',
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
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'segment',
            entityId: '50',
            dependsOnTag: 'segment:50',
        ));

        $this->invalidatedCacheTagCache->invalidate(
            'activity:100',
            'segment:50',
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
            dependsOnTag: 'activity:100',
        ));

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testFindReturnsEmptyWhenNoDependenciesRegistered(): void
    {
        $this->invalidatedCacheTagCache->invalidate('activity:100');

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testClearForEntityThenRegisterReplacesPreviousDependencies(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'best-effort:1000:Run',
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'activity:100',
        ));

        $this->invalidatedCacheTagCache->invalidate('best-effort:1000:Run');

        $dirtyIds = $this->cacheTagDependencyCache->findEntityIdsThatDependOnInvalidatedTags('activity');
        $this->assertEmpty($dirtyIds);
    }

    public function testClearForEntityRemovesDependencies(): void
    {
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '100',
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: '200',
            dependsOnTag: 'activity:200',
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->invalidatedCacheTagCache->invalidate(
            'activity:100',
            'activity:200',
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
            dependsOnTag: 'activity:100',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'segment',
            entityId: '100',
            dependsOnTag: 'segment:100',
        ));

        $this->cacheTagDependencyCache->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );
        $this->invalidatedCacheTagCache->invalidate('segment:100');

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
            dependsOnTag: 'activity:A',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'A',
            dependsOnTag: 'best-effort:1000:Run',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'B',
            dependsOnTag: 'activity:B',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'B',
            dependsOnTag: 'best-effort:1000:Run',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'C',
            dependsOnTag: 'activity:C',
        ));
        $this->cacheTagDependencyCache->register(CacheTagDependency::fromState(
            entityType: 'activity',
            entityId: 'C',
            dependsOnTag: 'best-effort:5000:Run',
        ));

        $this->invalidatedCacheTagCache->invalidate(
            'activity:B',
            'best-effort:1000:Run',
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

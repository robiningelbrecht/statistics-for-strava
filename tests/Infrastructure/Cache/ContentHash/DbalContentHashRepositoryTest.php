<?php

namespace App\Tests\Infrastructure\Cache\ContentHash;

use App\Infrastructure\Cache\ContentHash\ContentHash;
use App\Infrastructure\Cache\ContentHash\ContentHashRepository;
use App\Infrastructure\Cache\ContentHash\DbalContentHashRepository;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;

class DbalContentHashRepositoryTest extends ContainerTestCase
{
    private ContentHashRepository $contentHashRepository;

    public function testSaveAndFind(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'abc123',
        ));

        $this->assertEquals(
            expected: 'abc123',
            actual: $this->contentHashRepository->find(
                entityType: 'activity',
                entityId: '100',
            ),
        );
    }

    public function testSaveOverwritesExistingHash(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'abc123',
        ));
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'def456',
        ));

        $this->assertEquals(
            expected: 'def456',
            actual: $this->contentHashRepository->find(
                entityType: 'activity',
                entityId: '100',
            ),
        );
    }

    public function testFindThrowsWhenNotFound(): void
    {
        $this->expectException(EntityNotFound::class);

        $this->contentHashRepository->find(
            entityType: 'activity',
            entityId: '999',
        );
    }

    public function testFindDistinguishesByEntityType(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'activity-hash',
        ));
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'segment',
            entityId: '100',
            hash: 'segment-hash',
        ));

        $this->assertEquals(
            expected: 'activity-hash',
            actual: $this->contentHashRepository->find(
                entityType: 'activity',
                entityId: '100',
            ),
        );
        $this->assertEquals(
            expected: 'segment-hash',
            actual: $this->contentHashRepository->find(
                entityType: 'segment',
                entityId: '100',
            ),
        );
    }

    public function testClearForEntity(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'abc123',
        ));

        $this->contentHashRepository->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );

        $this->expectException(EntityNotFound::class);
        $this->contentHashRepository->find(
            entityType: 'activity',
            entityId: '100',
        );
    }

    public function testClearForEntityDoesNotAffectOtherEntities(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'hash-100',
        ));
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '200',
            hash: 'hash-200',
        ));

        $this->contentHashRepository->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );

        $this->assertEquals(
            expected: 'hash-200',
            actual: $this->contentHashRepository->find(
                entityType: 'activity',
                entityId: '200',
            ),
        );
    }

    public function testClearForEntityDoesNotAffectOtherEntityTypes(): void
    {
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'activity',
            entityId: '100',
            hash: 'activity-hash',
        ));
        $this->contentHashRepository->save(ContentHash::fromState(
            entityType: 'segment',
            entityId: '100',
            hash: 'segment-hash',
        ));

        $this->contentHashRepository->clearForEntity(
            entityType: 'activity',
            entityId: '100',
        );

        $this->assertEquals(
            expected: 'segment-hash',
            actual: $this->contentHashRepository->find(
                entityType: 'segment',
                entityId: '100',
            ),
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->contentHashRepository = new DbalContentHashRepository(
            $this->getConnection()
        );
    }
}

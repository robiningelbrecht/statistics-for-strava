<?php

namespace App\Tests;

use App\Domain\Strava\Activity\ActivityIntensity;
use App\Domain\Strava\Activity\ActivityRepository;
use App\Domain\Strava\Activity\ActivityTotals;
use App\Domain\Strava\Activity\Eddington\Eddington;
use App\Domain\Strava\Activity\Stream\StreamBasedActivityHeartRateRepository;
use Carbon\Carbon;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolsException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Translation\LocaleSwitcher;

abstract class ContainerTestCase extends KernelTestCase
{
    protected static ?Connection $ourDbalConnection = null;

    /**
     * @throws ToolsException
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$ourDbalConnection) {
            self::bootKernel();
            self::$ourDbalConnection = self::getContainer()->get(Connection::class);
        }

        $this->createTestDatabase();

        // Empty the static cache between tests.
        $this->getContainer()->get(ActivityRepository::class)::$cachedActivities = [];
        $this->getContainer()->get(ActivityIntensity::class)::$cachedIntensities = [];
        $this->getContainer()->get(StreamBasedActivityHeartRateRepository::class)::$cachedHeartRateZones = [];
        $this->getContainer()->get(StreamBasedActivityHeartRateRepository::class)::$cachedHeartRateZonesInLastXDays = [];
        Eddington::$instances = [];
        ActivityTotals::$instance = null;

        // Empty file systems.
        /** @var \League\Flysystem\FilesystemOperator[] $fileSystems */
        $fileSystems = [
            $this->getContainer()->get('default.storage'),
            $this->getContainer()->get('public.storage'),
            $this->getContainer()->get('file.storage'),
            $this->getContainer()->get('build.storage'),
        ];

        foreach ($fileSystems as $fileSystem) {
            $fileSystem->deleteDirectory('/');
        }

        // Make sure every test is initialized with the default locale.
        /** @var LocaleSwitcher $localeSwitcher */
        $localeSwitcher = $this->getContainer()->get(LocaleSwitcher::class);
        $localeSwitcher->reset();
        Carbon::setLocale($localeSwitcher->getLocale());
    }

    /**
     * @throws ToolsException
     */
    private function createTestDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);
    }

    protected function getConnection(): Connection
    {
        return self::$ourDbalConnection;
    }
}

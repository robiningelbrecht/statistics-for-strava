<?php

namespace App\Tests;

use App\Domain\Activity\ActivityIntensity;
use App\Domain\Activity\ActivityTotals;
use App\Domain\Activity\DailyTrainingLoad;
use App\Domain\Activity\Eddington\Eddington;
use App\Domain\Activity\EnrichedActivities;
use App\Domain\Activity\Stream\StreamBasedActivityHeartRateRepository;
use App\Domain\Activity\Stream\StreamBasedActivityPowerRepository;
use App\Infrastructure\Twig\HtmlTwigExtension;
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

        if (!self::$ourDbalConnection instanceof Connection) {
            self::bootKernel();
            self::$ourDbalConnection = self::getContainer()->get(Connection::class);
        }

        $this->createTestDatabase();

        // Empty the static cache between tests.
        EnrichedActivities::$cachedActivities = [];
        EnrichedActivities::$cachedActivitiesPerActivityType = [];
        DailyTrainingLoad::$cachedLoad = [];
        ActivityIntensity::$cachedIntensities = [];
        StreamBasedActivityPowerRepository::$cachedPowerOutputs = [];
        StreamBasedActivityPowerRepository::$cachedNormalizedPowers = [];
        StreamBasedActivityHeartRateRepository::$cachedHeartRateZones = [];
        StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesPerActivityType = [];
        StreamBasedActivityHeartRateRepository::$cachedHeartRateZonesInLastXDays = [];
        Eddington::$instances = [];
        ActivityTotals::$instance = null;
        HtmlTwigExtension::$seenIds = [];

        // Empty file systems.
        /** @var \League\Flysystem\FilesystemOperator[] $fileSystems */
        $fileSystems = [
            // $this->getContainer()->get('default.storage'),
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

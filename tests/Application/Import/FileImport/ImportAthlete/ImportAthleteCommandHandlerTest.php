<?php

declare(strict_types=1);

namespace App\Tests\Application\Import\FileImport\ImportAthlete;

use App\Application\Import\FileImport\ImportAthlete\ImportAthlete;
use App\Application\Import\FileImport\ImportAthlete\ImportAthleteCommandHandler;
use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteBirthDate;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Athlete\KeyValueBasedAthleteRepository;
use App\Domain\Athlete\MaxHeartRate\MaxHeartRateFormula;
use App\Domain\Athlete\RestingHeartRate\RestingHeartRateFormula;
use App\Infrastructure\Config\AppConfig;
use App\Infrastructure\Config\PlatformEnvironment;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\ValueObject\Identifier\FakeUuidFactory;
use App\Tests\SpyOutput;

class ImportAthleteCommandHandlerTest extends ContainerTestCase
{
    private ImportAthleteCommandHandler $importAthleteCommandHandler;
    private AthleteRepository $athleteRepository;

    public function testHandleCreatesNewAthleteWithRandomIdWhenNoneExists(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/app-configs/complete'),
            platformEnvironment: PlatformEnvironment::PROD,
            importMode: AppConfig::getImportMode(),
        );

        $this->importAthleteCommandHandler->handle(new ImportAthlete(new SpyOutput()));

        $athlete = $this->athleteRepository->find();
        $this->assertSame(FakeUuidFactory::random(), $athlete->getAthleteId());
        $this->assertSame('John Doe', (string) $athlete->getName());
        $this->assertTrue($athlete->isMale());
    }

    public function testHandleReusesExistingAthleteId(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/app-configs/complete'),
            platformEnvironment: PlatformEnvironment::PROD,
            importMode: AppConfig::getImportMode(),
        );

        $this->athleteRepository->save(Athlete::create([
            'id' => 'existing-athlete-id',
            'firstname' => 'Jane',
            'lastname' => 'Roe',
            'sex' => 'F',
            'birthDate' => AthleteBirthDate::fromString('1970-01-01'),
        ]));

        $this->importAthleteCommandHandler->handle(new ImportAthlete(new SpyOutput()));

        $athlete = $this->athleteRepository->find();

        $this->assertSame('existing-athlete-id', $athlete->getAthleteId());
        $this->assertSame('John Doe', (string) $athlete->getName());
        $this->assertTrue($athlete->isMale());
    }

    public function testHandleThrowsWhenFirstNameIsMissing(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/app-configs/missing-first-name'),
            platformEnvironment: PlatformEnvironment::PROD,
            importMode: AppConfig::getImportMode(),
        );

        $this->expectExceptionObject(new \RuntimeException('general.athlete.firstName configuration is missing'));
        $this->importAthleteCommandHandler->handle(new ImportAthlete(new SpyOutput()));
    }

    public function testHandleThrowsWhenLastNameIsMissing(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/app-configs/missing-last-name'),
            platformEnvironment: PlatformEnvironment::PROD,
            importMode: AppConfig::getImportMode(),
        );

        $this->expectExceptionObject(new \RuntimeException('general.athlete.lastName configuration is missing'));
        $this->importAthleteCommandHandler->handle(new ImportAthlete(new SpyOutput()));
    }

    public function testHandleThrowsWhenGenderIsMissing(): void
    {
        AppConfig::init(
            kernelProjectDir: KernelProjectDir::fromString(__DIR__.'/app-configs/missing-gender'),
            platformEnvironment: PlatformEnvironment::PROD,
            importMode: AppConfig::getImportMode(),
        );

        $this->expectExceptionObject(new \RuntimeException('general.athlete.gender configuration is missing'));
        $this->importAthleteCommandHandler->handle(new ImportAthlete(new SpyOutput()));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->athleteRepository = new KeyValueBasedAthleteRepository(
            $this->getContainer()->get(KeyValueStore::class),
            $this->getContainer()->get(MaxHeartRateFormula::class),
            $this->getContainer()->get(RestingHeartRateFormula::class),
        );

        $this->importAthleteCommandHandler = new ImportAthleteCommandHandler(
            AthleteBirthDate::fromString('1989-08-14'),
            $this->athleteRepository,
            new FakeUuidFactory(),
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        // Reset the global config to the default test config so other tests are unaffected.
        AppConfig::init(
            kernelProjectDir: $this->getContainer()->get(KernelProjectDir::class),
            platformEnvironment: PlatformEnvironment::TEST,
            importMode: AppConfig::getImportMode(),
        );

        parent::tearDown();
    }
}

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

class ImportAthleteCommandHandlerTest extends ContainerTestCase
{
    private AthleteRepository $athleteRepository;

    public function testHandleCreatesNewAthleteWithRandomIdWhenNoneExists(): void
    {
        $this->buildHandler(__DIR__.'/app-configs/complete')->handle(new ImportAthlete());

        $athlete = $this->athleteRepository->find();
        $this->assertSame(FakeUuidFactory::random(), $athlete->getAthleteId());
        $this->assertSame('John Doe', (string) $athlete->getName());
        $this->assertTrue($athlete->isMale());
    }

    public function testHandleReusesExistingAthleteId(): void
    {
        $this->athleteRepository->save(Athlete::create([
            'id' => 'existing-athlete-id',
            'firstname' => 'Jane',
            'lastname' => 'Roe',
            'sex' => 'F',
            'birthDate' => AthleteBirthDate::fromString('1970-01-01'),
        ]));

        $this->buildHandler(__DIR__.'/app-configs/complete')->handle(new ImportAthlete());

        $athlete = $this->athleteRepository->find();

        $this->assertSame('existing-athlete-id', $athlete->getAthleteId());
        $this->assertSame('John Doe', (string) $athlete->getName());
        $this->assertTrue($athlete->isMale());
    }

    public function testHandleThrowsWhenFirstNameIsMissing(): void
    {
        $this->expectExceptionObject(new \RuntimeException('general.athlete.firstName configuration is missing'));
        $this->buildHandler(__DIR__.'/app-configs/missing-first-name')->handle(new ImportAthlete());
    }

    public function testHandleThrowsWhenLastNameIsMissing(): void
    {
        $this->expectExceptionObject(new \RuntimeException('general.athlete.lastName configuration is missing'));
        $this->buildHandler(__DIR__.'/app-configs/missing-last-name')->handle(new ImportAthlete());
    }

    public function testHandleThrowsWhenGenderIsMissing(): void
    {
        $this->expectExceptionObject(new \RuntimeException('general.athlete.gender configuration is missing'));
        $this->buildHandler(__DIR__.'/app-configs/missing-gender')->handle(new ImportAthlete());
    }

    private function buildHandler(string $dir): ImportAthleteCommandHandler
    {
        AppConfig::setYamlConfigFilesToParse(
            kernelProjectDir: KernelProjectDir::fromString($dir),
            platformEnvironment: PlatformEnvironment::PROD,
        );

        $config = new AppConfig();

        return new ImportAthleteCommandHandler(
            AthleteBirthDate::fromString('1989-08-14'),
            $this->athleteRepository,
            new FakeUuidFactory(),
            $config,
        );
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
    }
}

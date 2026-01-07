<?php

namespace App\Tests\Console;

use App\Console\DetectCorruptedActivitiesConsoleCommand;
use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\WorldType;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DetectCorruptedActivitiesConsoleCommandTest extends ConsoleCommandTestCase
{
    use MatchesSnapshots;

    private DetectCorruptedActivitiesConsoleCommand $detectCorruptedActivitiesConsoleCommand;

    public function testExecuteWithoutCorruptedData(): void
    {
        $command = $this->getCommandInApplication('app:data:detect-corrupted-activities');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
    }

    public function testExecuteWithoutDataButNegativeConfirmation(): void
    {
        $this->getConnection()->executeStatement(
            'INSERT INTO Activity (activityId, data, startDateTime, sportType, name, distance, 
                                    elevation, averageSpeed, maxSpeed, movingTimeInSeconds, kudoCount,
                                    totalImageCount, worldType) 
                VALUES (:activityId, :data, :startDateTime, :sportType, :name, :distance, 
                        :elevation, :averageSpeed, :maxSpeed, :movingTimeInSeconds, :kudoCount,
                        :totalImageCount, :worldType)',
            [
                'activityId' => 'activity-test',
                'data' => '{"name": "Ride", "distance": 42,}',
                'startDateTime' => '2026-01-06',
                'sportType' => SportType::RIDE->value,
                'name' => 'Ride',
                'distance' => 4200,
                'elevation' => 4200,
                'averageSpeed' => 4200,
                'maxSpeed' => 4200,
                'movingTimeInSeconds' => 4200,
                'kudoCount' => 4200,
                'totalImageCount' => 1,
                'worldType' => WorldType::REAL_WORLD->value,
            ]
        );

        $command = $this->getCommandInApplication('app:data:detect-corrupted-activities');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['no']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
    }

    public function testExecuteWithoutDataButPositiveConfirmation(): void
    {
        $this->getConnection()->executeStatement(
            'INSERT INTO Activity (activityId, data, startDateTime, sportType, name, distance, 
                                    elevation, averageSpeed, maxSpeed, movingTimeInSeconds, kudoCount,
                                    totalImageCount, worldType) 
                VALUES (:activityId, :data, :startDateTime, :sportType, :name, :distance, 
                        :elevation, :averageSpeed, :maxSpeed, :movingTimeInSeconds, :kudoCount,
                        :totalImageCount, :worldType)',
            [
                'activityId' => 'activity-test',
                'data' => '{"name": "Ride", "distance": 42,}',
                'startDateTime' => '2026-01-06',
                'sportType' => SportType::RIDE->value,
                'name' => 'Ride',
                'distance' => 4200,
                'elevation' => 4200,
                'averageSpeed' => 4200,
                'maxSpeed' => 4200,
                'movingTimeInSeconds' => 4200,
                'kudoCount' => 4200,
                'totalImageCount' => 1,
                'worldType' => WorldType::REAL_WORLD->value,
            ]
        );

        $command = $this->getCommandInApplication('app:data:detect-corrupted-activities');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['yes']);
        $commandTester->execute([
            'command' => $command->getName(),
        ]);

        $this->assertMatchesSnapshot($commandTester->getDisplay(), new ConsoleOutputSnapshotDriver());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->detectCorruptedActivitiesConsoleCommand = $this->getContainer()->get(DetectCorruptedActivitiesConsoleCommand::class);
    }

    protected function getConsoleCommand(): Command
    {
        return $this->detectCorruptedActivitiesConsoleCommand;
    }
}

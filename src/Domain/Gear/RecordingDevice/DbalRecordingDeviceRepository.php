<?php

declare(strict_types=1);

namespace App\Domain\Gear\RecordingDevice;

use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Infrastructure\ValueObject\Measurement\Time\Seconds;
use Doctrine\DBAL\Connection;

final readonly class DbalRecordingDeviceRepository extends DbalRepository implements RecordingDeviceRepository
{
    public function __construct(
        Connection $connection,
        private RecordingDevicesConfig $recordingDevicesConfig,
    ) {
        parent::__construct($connection);
    }

    public function findAll(): RecordingDevices
    {
        $results = $this->connection->executeQuery(
            'SELECT DISTINCT deviceName,
                    SUM(movingTimeInSeconds) as totalMovingTime,
                    SUM(distance) as totalDistance,
                    SUM(elevation) as totalElevation,
                    COUNT(*) as activityCount
             FROM Activity
             WHERE deviceName IS NOT NULL
             GROUP BY deviceName
             ORDER BY activityCount DESC'
        )->fetchAllAssociative();

        return RecordingDevices::fromArray(array_map(
            function (array $result): RecordingDevice {
                $recordingDevice = RecordingDevice::fromState(
                    name: $result['deviceName'],
                    timeTracked: Seconds::from((float) $result['totalMovingTime']),
                    distanceTracked: Meter::from((float) $result['totalDistance'])->toKilometer(),
                    elevationTracked: Meter::from((float) $result['totalElevation']),
                    activityCount: (int) $result['activityCount'],
                );

                return $recordingDevice->withPurchasePrice(
                    $this->recordingDevicesConfig->getPurchasePrice($recordingDevice->getId())
                );
            },
            $results,
        ));
    }
}

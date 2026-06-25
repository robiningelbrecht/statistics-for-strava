<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\UpdateGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\UpdateGear\UpdateGear;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use Money\Money;
use PHPUnit\Framework\TestCase;

class UpdateGearTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
            'status' => 'retired',
            'purchasePriceAmount' => '1500.00',
            'purchasePriceCurrency' => 'EUR',
        ]);

        $this->assertEquals(GearId::fromUnprefixed('1'), $command->getGearId());
        $this->assertSame('My custom gear', $command->getName());
        $this->assertTrue($command->isRetired());
        $this->assertEquals(Money::EUR(150000), $command->getPurchasePrice());
    }

    public function testFromPayloadDefaultsToActiveWithoutPurchasePrice(): void
    {
        $command = UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
        ]);

        $this->assertFalse($command->isRetired());
        $this->assertNull($command->getPurchasePrice());
        $this->assertNull($command->getNewImage());
        $this->assertNull($command->getRemovedImage());
    }

    public function testFromPayloadWithNewImage(): void
    {
        $command = UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
            'localImagePath' => json_encode([
                ['status' => 'new', 'filename' => 'gear.png', 'content' => base64_encode('image-content')],
            ]),
        ]);

        $this->assertNotNull($command->getNewImage());
        $this->assertSame('gear.png', (string) $command->getNewImage()->getFilename());
        $this->assertNull($command->getRemovedImage());
    }

    public function testFromPayloadWithRemovedImage(): void
    {
        $command = UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
            'localImagePath' => json_encode([
                ['status' => 'removed', 'path' => '/files/gear/old.jpg'],
            ]),
        ]);

        $this->assertNull($command->getNewImage());
        $this->assertNotNull($command->getRemovedImage());
        $this->assertSame('files/gear/old.jpg', $command->getRemovedImage()->getPath()->toLocalImagePath());
    }

    public function testFromPayloadTrimsName(): void
    {
        $command = UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => '  My custom gear  ',
        ]);

        $this->assertSame('My custom gear', $command->getName());
    }

    public function testFromPayloadThrowsOnMissingGearId(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearId" and "name" are required.'));

        UpdateGear::fromPayload([
            'name' => 'My custom gear',
        ]);
    }

    public function testFromPayloadThrowsOnMissingName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "gearId" and "name" are required.'));

        UpdateGear::fromPayload([
            'gearId' => 'gear-1',
        ]);
    }

    public function testFromPayloadThrowsOnEmptyName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The name cannot be empty.'));

        UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => '   ',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidStatus(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The status is invalid.'));

        UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
            'status' => 'not-a-status',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidPurchasePrice(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The purchase price is invalid.'));

        UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'My custom gear',
            'purchasePriceAmount' => 'not-a-number',
        ]);
    }
}

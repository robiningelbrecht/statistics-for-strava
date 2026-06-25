<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\AddGear;

use App\Domain\Gear\AddGear\AddGear;
use App\Infrastructure\CQRS\Command\Deserialize\CouldNotDeserializeCommand;
use Money\Money;
use PHPUnit\Framework\TestCase;

class AddGearTest extends TestCase
{
    public function testFromPayload(): void
    {
        $command = AddGear::fromPayload([
            'name' => 'My custom gear',
            'status' => 'retired',
            'purchasePriceAmount' => '1500.00',
            'purchasePriceCurrency' => 'EUR',
        ]);

        $this->assertSame('My custom gear', $command->getName());
        $this->assertTrue($command->isRetired());
        $this->assertEquals(Money::EUR(150000), $command->getPurchasePrice());
    }

    public function testFromPayloadDefaultsToActiveWithoutPurchasePrice(): void
    {
        $command = AddGear::fromPayload([
            'name' => 'My custom gear',
        ]);

        $this->assertSame('My custom gear', $command->getName());
        $this->assertFalse($command->isRetired());
        $this->assertNull($command->getPurchasePrice());
        $this->assertNull($command->getNewImage());
    }

    public function testFromPayloadWithImage(): void
    {
        $command = AddGear::fromPayload([
            'name' => 'My custom gear',
            'localImagePath' => json_encode([
                ['status' => 'new', 'filename' => 'gear.jpg', 'content' => base64_encode('image-content')],
            ]),
        ]);

        $this->assertNotNull($command->getNewImage());
        $this->assertSame('gear.jpg', (string) $command->getNewImage()->getFilename());
        $this->assertSame('image-content', $command->getNewImage()->getContent());
    }

    public function testFromPayloadWithEmptyImageList(): void
    {
        $command = AddGear::fromPayload([
            'name' => 'My custom gear',
            'localImagePath' => '[]',
        ]);

        $this->assertNull($command->getNewImage());
    }

    public function testFromPayloadThrowsOnUnsupportedImageType(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('Unsupported image file type.'));

        AddGear::fromPayload([
            'name' => 'My custom gear',
            'localImagePath' => json_encode([
                ['status' => 'new', 'filename' => 'gear.gif', 'content' => base64_encode('image-content')],
            ]),
        ]);
    }

    public function testFromPayloadTrimsName(): void
    {
        $command = AddGear::fromPayload([
            'name' => '  My custom gear  ',
        ]);

        $this->assertSame('My custom gear', $command->getName());
    }

    public function testFromPayloadThrowsOnMissingName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('A "name" is required.'));

        AddGear::fromPayload([
            'status' => 'active',
        ]);
    }

    public function testFromPayloadThrowsOnEmptyName(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The name cannot be empty.'));

        AddGear::fromPayload([
            'name' => '   ',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidStatus(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The status is invalid.'));

        AddGear::fromPayload([
            'name' => 'My custom gear',
            'status' => 'not-a-status',
        ]);
    }

    public function testFromPayloadThrowsOnInvalidPurchasePrice(): void
    {
        $this->expectExceptionObject(CouldNotDeserializeCommand::invalidPayload('The purchase price is invalid.'));

        AddGear::fromPayload([
            'name' => 'My custom gear',
            'purchasePriceAmount' => 'not-a-number',
        ]);
    }
}

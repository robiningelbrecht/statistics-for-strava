<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\AddGear;

use App\Domain\Gear\AddGear\AddGear;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\GearType;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\ContainerTestCase;
use Money\Money;

class AddGearCommandHandlerTest extends ContainerTestCase
{
    private CommandBus $commandBus;
    private GearRepository $gearRepository;

    public function testHandle(): void
    {
        $this->commandBus->dispatch(AddGear::fromPayload([
            'name' => 'My custom gear',
            'status' => 'retired',
            'purchasePriceAmount' => '1500.00',
            'purchasePriceCurrency' => 'EUR',
        ]));

        $gears = $this->gearRepository->findAll();
        $this->assertCount(1, $gears);

        $gear = $gears->getFirst();
        $this->assertSame('My custom gear', $gear->getOriginalName());
        $this->assertSame(GearType::CUSTOM, $gear->getType());
        $this->assertTrue($gear->isRetired());
        $this->assertEquals(Money::EUR(150000), $gear->getPurchasePrice());
        $this->assertEquals(
            SerializableDateTime::fromString('2023-10-17 16:15:04'),
            $gear->getCreatedOn()
        );
    }

    public function testHandleWithoutPurchasePrice(): void
    {
        $this->commandBus->dispatch(AddGear::fromPayload([
            'name' => 'My custom gear',
        ]));

        $gear = $this->gearRepository->findAll()->getFirst();
        $this->assertSame('My custom gear', $gear->getOriginalName());
        $this->assertSame(GearType::CUSTOM, $gear->getType());
        $this->assertFalse($gear->isRetired());
        $this->assertNull($gear->getPurchasePrice());
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->gearRepository = $this->getContainer()->get(GearRepository::class);
    }
}

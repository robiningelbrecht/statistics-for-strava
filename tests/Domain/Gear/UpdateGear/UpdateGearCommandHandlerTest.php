<?php

declare(strict_types=1);

namespace App\Tests\Domain\Gear\UpdateGear;

use App\Domain\Gear\GearId;
use App\Domain\Gear\GearRepository;
use App\Domain\Gear\UpdateGear\UpdateGear;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\Exception\EntityNotFound;
use App\Tests\ContainerTestCase;
use App\Tests\Domain\Gear\GearBuilder;
use Money\Money;

class UpdateGearCommandHandlerTest extends ContainerTestCase
{
    private CommandBus $commandBus;
    private GearRepository $gearRepository;

    public function testHandle(): void
    {
        $this->gearRepository->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('1'))
                ->withName('Original gear')
                ->withIsRetired(false)
                ->build()
        );

        $this->commandBus->dispatch(UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'Updated gear',
            'status' => 'retired',
            'purchasePriceAmount' => '2000.00',
            'purchasePriceCurrency' => 'EUR',
        ]));

        $gear = $this->gearRepository->find(GearId::fromUnprefixed('1'));
        $this->assertSame('Updated gear', $gear->getOriginalName());
        $this->assertTrue($gear->isRetired());
        $this->assertEquals(Money::EUR(200000), $gear->getPurchasePrice());
    }

    public function testHandleKeepsExistingPurchasePriceWhenNoneGiven(): void
    {
        $this->gearRepository->add(
            GearBuilder::fromDefaults()
                ->withGearId(GearId::fromUnprefixed('1'))
                ->withName('Original gear')
                ->withPurchasePrice(Money::EUR(150000))
                ->build()
        );

        $this->commandBus->dispatch(UpdateGear::fromPayload([
            'gearId' => 'gear-1',
            'name' => 'Updated gear',
        ]));

        $gear = $this->gearRepository->find(GearId::fromUnprefixed('1'));
        $this->assertSame('Updated gear', $gear->getOriginalName());
        $this->assertFalse($gear->isRetired());
        $this->assertEquals(Money::EUR(150000), $gear->getPurchasePrice());
    }

    public function testHandleThrowsWhenGearNotFound(): void
    {
        $this->expectException(EntityNotFound::class);

        $this->commandBus->dispatch(UpdateGear::fromPayload([
            'gearId' => 'gear-999',
            'name' => 'Updated gear',
        ]));
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
        $this->gearRepository = $this->getContainer()->get(GearRepository::class);
    }
}

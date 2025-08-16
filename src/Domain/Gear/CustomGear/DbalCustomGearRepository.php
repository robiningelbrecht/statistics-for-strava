<?php

declare(strict_types=1);

namespace App\Domain\Gear\CustomGear;

use App\Domain\Gear\Gears;
use App\Domain\Gear\GearType;
use App\Domain\Gear\ProvideGearRepositoryHelpers;
use App\Infrastructure\Repository\DbalRepository;
use App\Infrastructure\ValueObject\String\Tag;
use Doctrine\DBAL\Connection;

final readonly class DbalCustomGearRepository extends DbalRepository implements CustomGearRepository
{
    use ProvideGearRepositoryHelpers {
        save as protected parentSave;
        findAll as protected parentFindAll;
    }

    public function __construct(
        Connection $connection,
        private CustomGearConfig $customGearConfig,
    ) {
        parent::__construct($connection);
    }

    protected function getConnection(): Connection
    {
        return $this->connection;
    }

    public function save(CustomGear $gear): void
    {
        $this->parentSave(
            gear: $gear,
            gearType: GearType::CUSTOM
        );
    }

    public function findAll(): Gears
    {
        $gears = $this->parentFindAll(
            gearType: GearType::CUSTOM
        );

        $gearsWithTags = Gears::empty();
        /** @var CustomGear $gear */
        foreach ($gears as $gear) {
            $gearsWithTags->add($gear->withFullTag(Tag::fromTags(
                (string) $this->customGearConfig->getHashtagPrefix(),
                $gear->getId()->toUnprefixedString()
            )));
        }

        return $gearsWithTags;
    }

    public function removeAll(): void
    {
        $this->connection->executeStatement('DELETE FROM gear WHERE type = :type', [
            'type' => GearType::CUSTOM->value,
        ]);
    }
}

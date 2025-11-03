<?php

declare(strict_types=1);

namespace App\BuildApp\BuildPhotosHtml;

use App\Domain\Activity\SportType\SportType;
use App\Domain\Activity\SportType\SportTypes;
use Symfony\Component\Intl\Countries;

final readonly class DefaultEnabledPhotoFilters implements \JsonSerializable
{
    private function __construct(
        private SportTypes $defaultEnabledSportTypes,
        private ?string $defaultEnabledCountryCode,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function from(array $config): self
    {
        if (!empty($config['countryCode']) && !in_array($config['countryCode'], Countries::getCountryCodes())) {
            throw new \RuntimeException(sprintf('Country code "%s" in defaultEnabledFilters is not supported', $config['countryCode']));
        }

        return new self(
            defaultEnabledSportTypes: SportTypes::fromArray(array_map(
                SportType::from(...),
                $config['sportTypes'] ?? [],
            )),
            defaultEnabledCountryCode: $config['countryCode'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $json = [];

        if (!$this->defaultEnabledSportTypes->isEmpty()) {
            $json['sportType'] = $this->defaultEnabledSportTypes;
        }

        if (!is_null($this->defaultEnabledCountryCode)) {
            $json['countryCode'] = $this->defaultEnabledCountryCode;
        }

        return $json;
    }
}

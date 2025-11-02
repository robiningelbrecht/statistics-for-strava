<?php

declare(strict_types=1);

namespace App\Domain\Strava;

use Psr\Http\Message\ResponseInterface;

final readonly class StravaRateLimits
{
    private function __construct(
        private int $fifteenMinRateUsage,
        private int $fifteenMinRateLimit,
        private int $fifteenMinReadRateUsage,
        private int $fifteenMinReadRateLimit,
        private int $dailyRateUsage,
        private int $dailyRateLimit,
        private int $dailyReadRateUsage,
        private int $dailyReadRateLimit,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): self
    {
        [$fifteenMinRateLimit, $dailyRateLimit] = explode(',', $response->getHeaderLine('x-ratelimit-limit'));
        [$fifteenMinRateUsage, $dailyRateUsage] = explode(',', $response->getHeaderLine('x-ratelimit-usage'));
        [$fifteenMinReadRateLimit, $dailyReadRateLimit] = explode(',', $response->getHeaderLine('x-readratelimit-limit'));
        [$fifteenMinReadRateUsage, $dailyReadRateUsage] = explode(',', $response->getHeaderLine('x-readratelimit-usage'));

        return new self(
            fifteenMinRateUsage: (int) $fifteenMinRateUsage,
            fifteenMinRateLimit: (int) $fifteenMinRateLimit,
            fifteenMinReadRateUsage: (int) $fifteenMinReadRateUsage,
            fifteenMinReadRateLimit: (int) $fifteenMinReadRateLimit,
            dailyRateUsage: (int) $dailyRateUsage,
            dailyRateLimit: (int) $dailyRateLimit,
            dailyReadRateUsage: (int) $dailyReadRateUsage,
            dailyReadRateLimit: (int) $dailyReadRateLimit
        );
    }

    public function getFifteenMinRateUsage(): int
    {
        return $this->fifteenMinRateUsage;
    }

    public function getFifteenMinRateLimit(): int
    {
        return $this->fifteenMinRateLimit;
    }

    public function getFifteenMinReadRateUsage(): int
    {
        return $this->fifteenMinReadRateUsage;
    }

    public function getFifteenMinReadRateLimit(): int
    {
        return $this->fifteenMinReadRateLimit;
    }

    public function getDailyRateUsage(): int
    {
        return $this->dailyRateUsage;
    }

    public function getDailyRateLimit(): int
    {
        return $this->dailyRateLimit;
    }

    public function getDailyReadRateUsage(): int
    {
        return $this->dailyReadRateUsage;
    }

    public function getDailyReadRateLimit(): int
    {
        return $this->dailyReadRateLimit;
    }
}

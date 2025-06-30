<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tool;

use App\Domain\Strava\Ftp\FtpHistory;
use NeuronAI\Tools\Tool;

final class GetFtpHistory extends Tool
{
    public function __construct(
        private readonly FtpHistory $ftpHistory,
    ) {
        parent::__construct(
            'get_ftp_history',
            'Retrieves ftp history from database',
        );
    }

    /**
     * @return array<int, mixed>
     */
    public function __invoke(): array
    {
        return $this->ftpHistory->exportForAITooling();
    }
}

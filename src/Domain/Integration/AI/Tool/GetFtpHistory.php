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
            <<<DESC
            Retrieves the athlete’s Functional Threshold Power (FTP) history from the database.
            Use this tool when the user asks about changes in their FTP over time or when you need to determine intensity
            Returns a timeline of FTP values with corresponding dates.
            DESC
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

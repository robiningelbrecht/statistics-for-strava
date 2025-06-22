<?php

declare(strict_types=1);

namespace App\Domain\Integration\AI\Tools;

use App\Domain\Strava\Ftp\Ftp;
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
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        $history = [];
        /** @var Ftp $ftp */
        foreach ($this->ftpHistory->findAll() as $ftp) {
            $history[(string) $ftp->getSetOn()] = $ftp->getFtp()->getValue();
        }

        return $history;
    }
}

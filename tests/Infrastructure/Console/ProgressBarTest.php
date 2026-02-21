<?php

namespace App\Tests\Infrastructure\Console;

use App\Infrastructure\Console\ProgressBar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ProgressBarTest extends TestCase
{
    public function testStart(): void
    {
        $output = new BufferedOutput();
        $progressBar = new ProgressBar($output, 10);

        $progressBar->start();

        $display = $output->fetch();
        $this->assertStringContainsString('0/10', $display);
        $this->assertStringContainsString('Initializing...', $display);
    }

    public function testAdvance(): void
    {
        $output = new BufferedOutput();
        $progressBar = new ProgressBar($output, 10);

        $progressBar->start();
        $output->fetch();

        $progressBar->advance();

        $display = $output->fetch();
        $this->assertStringContainsString('1/10', $display);
    }

    public function testFinish(): void
    {
        $output = new BufferedOutput();
        $progressBar = new ProgressBar($output, 2);

        $progressBar->start();
        $output->fetch();

        $progressBar->finish();

        $display = $output->fetch();
        $this->assertStringContainsString('2/2', $display);
        $this->assertStringEndsWith("\n", $display);
    }

    public function testUpdateMessage(): void
    {
        $output = new BufferedOutput();
        $progressBar = new ProgressBar($output, 10);

        $progressBar->start();
        $output->fetch();

        $progressBar->updateMessage('Processing activities...');
        $progressBar->advance();

        $display = $output->fetch();
        $this->assertStringContainsString('Processing activities...', $display);
    }

    public function testFullLifecycle(): void
    {
        $output = new BufferedOutput();
        $progressBar = new ProgressBar($output, 3);

        $progressBar->start();
        $progressBar->updateMessage('Step 1');
        $progressBar->advance();
        $progressBar->updateMessage('Step 2');
        $progressBar->advance();
        $progressBar->updateMessage('Step 3');
        $progressBar->advance();
        $progressBar->finish();

        $display = $output->fetch();
        $this->assertStringContainsString('3/3', $display);
        $this->assertStringEndsWith("\n", $display);
    }
}

<?php

namespace App\Tests\Controller\Admin;

use App\Domain\Import\FileImportId;
use App\Domain\Import\FileImportRepository;
use App\Domain\Import\ImportMode;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use App\Tests\Domain\Import\FileImportBuilder;

class FileImportsRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testRendersTheGatedPanelWhenNotInFileImportMode(): void
    {
        $this->withImportMode(ImportMode::STRAVA_API);
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseIsSuccessful();
        $gatedPanel = $crawler->filter('[role="alert"][type="gated-panel"]');
        $this->assertCount(1, $gatedPanel);
        $this->assertStringContainsString(
            'File imports are only available in file import mode',
            $gatedPanel->text()
        );
    }

    public function testRendersTheTableWithoutGatedPanelInFileImportMode(): void
    {
        $this->withImportMode(ImportMode::FILES);
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('[role="alert"][type="gated-panel"]'));
        $this->assertCount(1, $crawler->filter('table.data-table'));
    }

    public function testRendersTheEmptyStateWhenThereAreNoImports(): void
    {
        $this->withImportMode(ImportMode::FILES);
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('No files imported yet.', $crawler->filter('body')->text());
        $this->assertCount(1, $crawler->filter('table.data-table tbody td[colspan="4"]'));
        $this->assertCount(0, $crawler->filter('[aria-label="Go to next page"]'));
    }

    public function testRendersTheTableWithoutPaginationForASinglePage(): void
    {
        $this->withImportMode(ImportMode::FILES);
        $this->seedFileImports(3);
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('table.data-table tbody tr'));
        $this->assertStringContainsString('activity-1.fit', $crawler->filter('table.data-table')->text());
        $this->assertStringNotContainsString('No files imported yet.', $crawler->filter('body')->text());
        $this->assertCount(0, $crawler->filter('[aria-label="Go to next page"]'));
    }

    public function testRendersTheTableWithPaginationWhenResultsExceedASinglePage(): void
    {
        $this->withImportMode(ImportMode::FILES);
        $this->seedFileImports(30);
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/file-imports');

        $this->assertResponseIsSuccessful();
        $this->assertCount(25, $crawler->filter('table.data-table tbody tr'));
        $this->assertCount(1, $crawler->filter('[aria-label="Go to next page"]'));
        $this->assertStringContainsString('of 30', $crawler->filter('body')->text());
    }

    private function seedFileImports(int $count): void
    {
        $fileImportRepository = static::getContainer()->get(FileImportRepository::class);

        for ($i = 1; $i <= $count; ++$i) {
            $fileImportRepository->add(
                FileImportBuilder::fromDefaults()
                    ->withFileImportId(FileImportId::fromUnprefixed((string) $i))
                    ->withOriginalFilename(sprintf('activity-%d.fit', $i))
                    ->withFileHash('hash-'.$i)
                    ->withImportedOn(SerializableDateTime::fromString(sprintf('2026-06-01 08:%02d:00', $i)))
                    ->build()
            );
        }
    }
}

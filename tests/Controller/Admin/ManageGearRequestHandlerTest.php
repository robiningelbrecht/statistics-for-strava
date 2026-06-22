<?php

namespace App\Tests\Controller\Admin;

use App\Domain\Gear\GearId;
use App\Domain\Gear\ImportedGear\ImportedGearRepository;
use App\Infrastructure\ValueObject\Measurement\Length\Meter;
use App\Tests\Domain\Gear\ImportedGear\ImportedGearBuilder;

class ManageGearRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/gear');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testRendersTheEmptyStateWhenThereAreNoGears(): void
    {
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/gear');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('table.data-table'));
        $this->assertStringContainsString('No gears added yet.', $crawler->filter('body')->text());
        $this->assertCount(1, $crawler->filter('table.data-table tbody td[colspan="5"]'));
        $this->assertCount(0, $crawler->filter('table.data-table tbody a[title="Edit"]'));
    }

    public function testRendersTheTableWithGears(): void
    {
        $importedGearRepository = static::getContainer()->get(ImportedGearRepository::class);

        for ($i = 1; $i <= 3; ++$i) {
            $importedGearRepository->save(
                ImportedGearBuilder::fromDefaults()
                    ->withGearId(GearId::fromUnprefixed((string) $i))
                    ->withName(sprintf('Gear %d', $i))
                    ->withDistanceInMeter(Meter::from(1000 * $i))
                    ->build()
            );
        }

        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/gear');

        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $crawler->filter('table.data-table tbody tr'));
        $this->assertStringContainsString('Gear 1', $crawler->filter('table.data-table')->text());
        $this->assertStringNotContainsString('No gears added yet.', $crawler->filter('body')->text());

        $editLinks = $crawler->filter('table.data-table tbody a[title="Edit"]');
        $this->assertCount(3, $editLinks);
        $this->assertStringContainsString(
            '/admin/gear/'.GearId::fromUnprefixed('1').'/edit',
            $editLinks->first()->attr('href')
        );
    }
}

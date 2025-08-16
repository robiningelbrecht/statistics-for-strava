<?php

namespace App\Tests\Domain\Manifest\BuildManifest;

use App\Domain\Athlete\Athlete;
use App\Domain\Athlete\AthleteRepository;
use App\Domain\Manifest\BuildManifest\BuildManifest;
use App\Infrastructure\CQRS\Command\Bus\CommandBus;
use App\Infrastructure\ValueObject\String\KernelProjectDir;
use App\Tests\ContainerTestCase;
use App\Tests\Infrastructure\FileSystem\provideAssertFileSystem;
use App\Tests\ProvideTestData;
use League\Flysystem\FilesystemOperator;

class BuildManifestCommandHandlerTest extends ContainerTestCase
{
    use ProvideTestData;
    use provideAssertFileSystem;

    private CommandBus $commandBus;

    public function testHandle(): void
    {
        /** @var AthleteRepository $athleteRepository */
        $athleteRepository = $this->getContainer()->get(AthleteRepository::class);
        $athleteRepository->save(Athlete::create([
            'id' => 100,
            'birthDate' => '1989-08-14',
            'firstname' => 'Robin',
            'lastname' => 'Ingelbrecht',
        ]));

        /** @var FilesystemOperator $publicStorage */
        $publicStorage = $this->getContainer()->get('public.storage');
        $publicStorage->write('manifest.json', '{"id":"[APP_HOST]","name":"[APP_NAME]","short_name":"Strava Statistics","description":"Strava Statistics is a self-hosted web app designed to provide you with better stats.","categories":["strava","statistics","utilities"],"start_url":"/","scope":"[APP_HOST]","display":"standalone","display_override":["fullscreen","minimal-ui"],"orientation":"portrait","theme_color":"#F26822","background_color":"#f9fafb","icons":[{"src":"[APP_BASE_PATH]/assets/images/manifest/icon-192.png","sizes":"192x192","type":"image/png"},{"src":"/assets/images/manifest/icon-192.maskable.png","sizes":"192x192","type":"image/png","purpose":"maskable"},{"src":"/assets/images/manifest/icon-512.png","sizes":"512x512","type":"image/png"},{"src":"/assets/images/manifest/icon-512.maskable.png","sizes":"512x512","type":"image/png","purpose":"maskable"},{"src":"/assets/images/manifest/icon-512.png","sizes":"any","type":"image/png"},{"src":"/assets/images/manifest/icon-512.maskable.png","sizes":"any","type":"image/png","purpose":"maskable"}],"screenshots":[{"src":"/assets/images/manifest/screenshots/dashboard.jpeg","sizes":"750x1600","type":"image/jpeg","form_factor":"narrow","label":"Dashboard"},{"src":"/assets/images/manifest/screenshots/heatmap.jpeg","sizes":"750x1600","type":"image/jpeg","form_factor":"narrow","label":"Heatmap"}]}');

        $this->commandBus->dispatch(new BuildManifest());
        $this->assertFileSystemWrites($publicStorage);
    }

    public function testThatManifestContainsPlaceholders(): void
    {
        $manifestContents = file_get_contents($this->getContainer()->get(KernelProjectDir::class).'/public/manifest.json');

        $this->assertStringContainsString(
            '[APP_HOST]',
            $manifestContents,
            'The manifest.json file should contain the [APP_HOST] placeholder. You probably need to run "git checkout origin/master -- public/manifest.json"'
        );
        $this->assertStringContainsString(
            '[APP_NAME]',
            $manifestContents,
            'The manifest.json file should contain the [APP_NAME] placeholder. You probably need to run "git checkout origin/master -- public/manifest.json"'
        );
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->getContainer()->get(CommandBus::class);
    }
}

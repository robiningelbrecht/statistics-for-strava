<?php

namespace App\Tests\Controller\Admin\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Domain\Gear\GearId;
use App\Domain\Import\ImportMode;
use App\Tests\Controller\Admin\AdminWebTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class ManageActivityFormRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/edit');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testAnonymousUsersAreRedirectedToTheLoginPageOnDelete(): void
    {
        $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/delete');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testRendersTheEditFormPrefilledWithTheActivity(): void
    {
        static::getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Morning Run')
                ->withGearId(GearId::fromUnprefixed('5'))
                ->withDeviceName('Garmin Edge')
                ->withIsCommute(true)
                ->withLocalImagePaths('activity-1/image.jpg')
                ->build(),
            [],
        ));

        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/edit');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Edit activity', $crawler->filter('h3')->text());

        $form = $crawler->filter('form[data-dispatch-command="update-activity"]');
        $this->assertCount(1, $form);

        $this->assertSame((string) ActivityId::fromUnprefixed('1'), $form->filter('input[name="activityId"]')->attr('value'));
        $this->assertSame('Morning Run', $form->filter('input[name="name"]')->attr('value'));

        // All Strava-sourced fields are disabled.
        $this->assertNotNull($crawler->filter('input#activity-name')->attr('disabled'));
        $this->assertNotNull($crawler->filter('select#activity-sport-type')->attr('disabled'));
        $this->assertNotNull($crawler->filter('select#activity-gear')->attr('disabled'));
        $this->assertNotNull($crawler->filter('select#activity-device-name')->attr('disabled'));
        $this->assertNotNull($crawler->filter('input#activity-is-commute')->attr('disabled'));

        // Disabled fields are not submitted, so their values are mirrored into hidden inputs.
        $this->assertSame('Morning Run', $crawler->filter('input[type="hidden"][name="name"]')->attr('value'));
        $this->assertCount(1, $crawler->filter('input[type="hidden"][name="sportType"]'));
        $this->assertSame((string) GearId::fromUnprefixed('5'), $crawler->filter('input[type="hidden"][name="gearId"]')->attr('value'));
        $this->assertSame('Garmin Edge', $crawler->filter('input[type="hidden"][name="deviceName"]')->attr('value'));
        // The commute checkbox can't submit while disabled, so its real value is preserved.
        $this->assertSame('true', $crawler->filter('input[type="hidden"][name="isCommute"]')->attr('value'));

        // The image upload is hidden entirely, since images can't be managed in Strava API mode.
        $this->assertCount(0, $crawler->filter('[data-image-dropzone]'));
    }

    public function testFilesModeKeepsFieldsEditableAndShowsImages(): void
    {
        $this->withImportMode(ImportMode::FILES);

        static::getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Morning Run')
                ->withIsCommute(false)
                ->build(),
            [],
        ));

        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/edit');

        $this->assertResponseIsSuccessful();

        $this->assertNull($crawler->filter('input#activity-name')->attr('disabled'));
        $this->assertNull($crawler->filter('select#activity-sport-type')->attr('disabled'));
        $this->assertNull($crawler->filter('select#activity-gear')->attr('disabled'));
        $this->assertNull($crawler->filter('select#activity-device-name')->attr('disabled'));
        $this->assertNull($crawler->filter('input#activity-is-commute')->attr('disabled'));

        $this->assertCount(0, $crawler->filter('input[type="hidden"][name="name"]'));
        $this->assertCount(0, $crawler->filter('input[type="hidden"][name="sportType"]'));
        $this->assertCount(0, $crawler->filter('input[type="hidden"][name="gearId"]'));
        $this->assertCount(0, $crawler->filter('input[type="hidden"][name="deviceName"]'));
        $this->assertSame('false', $crawler->filter('input[type="hidden"][name="isCommute"]')->attr('value'));

        // The image upload is available.
        $this->assertCount(1, $crawler->filter('[data-image-dropzone]'));
    }

    public function testRendersTheDeleteConfirmationForTheActivity(): void
    {
        static::getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Morning Run')
                ->build(),
            [],
        ));

        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/delete');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Delete activity', $crawler->filter('h3')->text());
        $this->assertStringContainsString('Morning Run', $crawler->filter('body')->text());

        $form = $crawler->filter('form[data-dispatch-command="delete-activity"]');
        $this->assertCount(1, $form);

        $this->assertSame((string) ActivityId::fromUnprefixed('1'), $form->filter('input[name="activityId"]')->attr('value'));
    }
}

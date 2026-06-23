<?php

namespace App\Tests\Controller\Admin\Activity;

use App\Domain\Activity\ActivityId;
use App\Domain\Activity\ActivityRepository;
use App\Domain\Activity\ActivityWithRawData;
use App\Tests\Controller\Admin\AdminWebTestCase;
use App\Tests\Domain\Activity\ActivityBuilder;

class ManageActivityFormRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/activities/'.ActivityId::fromUnprefixed('1').'/edit');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testRendersTheEditFormPrefilledWithTheActivity(): void
    {
        static::getContainer()->get(ActivityRepository::class)->add(ActivityWithRawData::fromState(
            ActivityBuilder::fromDefaults()
                ->withActivityId(ActivityId::fromUnprefixed('1'))
                ->withName('Morning Run')
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
    }
}

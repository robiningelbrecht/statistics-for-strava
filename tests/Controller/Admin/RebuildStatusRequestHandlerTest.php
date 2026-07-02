<?php

namespace App\Tests\Controller\Admin;

use App\Infrastructure\KeyValue\Key;
use App\Infrastructure\KeyValue\KeyValue;
use App\Infrastructure\KeyValue\KeyValueStore;
use App\Infrastructure\KeyValue\Value;
use App\Infrastructure\Serialization\Json;

class RebuildStatusRequestHandlerTest extends AdminWebTestCase
{
    public function testAnonymousUsersAreRedirectedToTheLoginPage(): void
    {
        $this->client->request('GET', '/admin/rebuildStatus');

        $this->assertResponseRedirects('/admin/login');
    }

    public function testItIsPendingWhenTheFlagIsSet(): void
    {
        $this->client->loginUser($this->adminUser());

        /** @var KeyValueStore $keyValueStore */
        $keyValueStore = static::getContainer()->get(KeyValueStore::class);
        $keyValueStore->save(KeyValue::fromState(
            key: Key::FORCE_REBUILD,
            value: Value::fromString('1'),
        ));

        $this->client->request('GET', '/admin/rebuildStatus');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['pending' => true], Json::decode($this->client->getResponse()->getContent()));
    }

    public function testItIsNotPendingWhenTheFlagIsNotSet(): void
    {
        $this->client->loginUser($this->adminUser());

        $this->client->request('GET', '/admin/rebuildStatus');

        $this->assertResponseIsSuccessful();
        $this->assertSame(['pending' => false], Json::decode($this->client->getResponse()->getContent()));
    }
}

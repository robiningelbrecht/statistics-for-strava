<?php

namespace App\Tests\Controller\Admin;

use Symfony\Component\HttpFoundation\Response;

class LoginLogoutRequestHandlerTest extends AdminWebTestCase
{
    public function testLoginPageIsAccessibleForAnonymousUsers(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('input[name="_username"]'));
        $this->assertCount(1, $crawler->filter('input[name="_password"]'));
        $this->assertCount(1, $crawler->filter('input[name="_csrf_token"]'));
    }

    public function testAuthenticatedUsersAreRedirectedAwayFromTheLoginPage(): void
    {
        $this->client->loginUser($this->adminUser());

        $this->client->request('GET', '/admin/login');

        $this->assertResponseRedirects('/admin/upload');
    }

    public function testCanLogInWithValidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');

        $this->client->submit($crawler->filter('form')->form([
            '_username' => self::ADMIN_USERNAME,
            '_password' => self::ADMIN_PASSWORD,
        ]));

        $this->assertResponseRedirects('/admin/upload');

        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCannotLogInWithInvalidCredentials(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');

        $this->client->submit($crawler->filter('form')->form([
            '_username' => self::ADMIN_USERNAME,
            '_password' => 'wrong-password',
        ]));

        $this->assertResponseRedirects('/admin/login');

        $crawler = $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.text-red-700'));
    }

    public function testLoggingOutRedirectsToTheLoginPage(): void
    {
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', '/admin/upload');
        $this->assertResponseIsSuccessful();

        $this->client->submit($crawler->filter('form[action$="/admin/logout"]')->form());
        $this->assertResponseRedirects('/admin/login');

        // The session no longer holds an authenticated user, so protected pages bounce back to login.
        $this->client->request('GET', '/admin/upload');
        $this->assertResponseRedirects('/admin/login', Response::HTTP_FOUND);
    }
}

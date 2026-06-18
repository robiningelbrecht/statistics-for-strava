<?php

namespace App\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\DataProvider;

class FileUploadRequestHandlerTest extends AdminWebTestCase
{
    public static function provideAdminPaths(): iterable
    {
        yield 'root admin path' => ['/admin'];
        yield 'upload path' => ['/admin/upload'];
    }

    #[DataProvider('provideAdminPaths')]
    public function testAnonymousUsersAreRedirectedToTheLoginPage(string $path): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseRedirects('/admin/login');
    }

    #[DataProvider('provideAdminPaths')]
    public function testRendersTheDashboardForAuthenticatedUsers(string $path): void
    {
        $this->client->loginUser($this->adminUser());

        $crawler = $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Upload activity files', $crawler->filter('body')->text());
    }
}

<?php

namespace App\Tests\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;

abstract class AdminWebTestCase extends WebTestCase
{
    protected const string ADMIN_USERNAME = 'admin';
    protected const string ADMIN_PASSWORD = 'admin-password';

    protected KernelBrowser $client;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER['ADMIN_USERNAME'] = $_ENV['ADMIN_USERNAME'] = self::ADMIN_USERNAME;
        $_SERVER['ADMIN_PASSWORD_HASH'] = $_ENV['ADMIN_PASSWORD_HASH'] = password_hash(
            self::ADMIN_PASSWORD,
            PASSWORD_BCRYPT,
            ['cost' => 4],
        );

        $this->client = static::createClient();
        $this->createTestDatabase();
    }

    protected function adminUser(): InMemoryUser
    {
        return new InMemoryUser(
            self::ADMIN_USERNAME,
            (string) $_SERVER['ADMIN_PASSWORD_HASH'],
            ['ROLE_ADMIN'],
        );
    }

    private function createTestDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);
    }
}

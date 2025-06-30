<?php

namespace App\Tests\Infrastructure;

use App\Tests\ContainerTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\ArrayInput;

class MigrationTest extends ContainerTestCase
{
    public function testItShouldContainAllMigrations(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $this->prepareDatabaseSchema($entityManager);
        $this->runMigrations();

        $schemaTool = new SchemaTool($entityManager);
        $statements = $schemaTool->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());

        $key = array_search('DROP TABLE migration_versions', $statements);
        if (false !== $key) {
            unset($statements[$key]);
        }

        $this->assertEmpty($statements, 'MISSING MIGRATION: '.PHP_EOL.implode(PHP_EOL.PHP_EOL, $statements));
    }

    private function prepareDatabaseSchema(EntityManagerInterface $entityManager): void
    {
        // Make sure db is empty before we run migrations.
        $schemaTool = new SchemaTool($entityManager);
        $classes = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($classes);
        $this->getConnection()->executeQuery('DROP table IF EXISTS migration_versions');
    }

    private function runMigrations(): void
    {
        /** @var \Doctrine\Migrations\DependencyFactory $dependencyFactory */
        $dependencyFactory = $this->getContainer()->get('doctrine.migrations.dependency_factory');

        $version = $dependencyFactory->getVersionAliasResolver()->resolveVersionAlias('latest');
        $planCalculator = $dependencyFactory->getMigrationPlanCalculator();
        $plan = $planCalculator->getPlanUntilVersion($version);
        $migrator = $dependencyFactory->getMigrator();
        $migratorConfigurationFactory = $dependencyFactory->getConsoleInputMigratorConfigurationFactory();
        $migratorConfiguration = $migratorConfigurationFactory->getMigratorConfiguration(new ArrayInput([]));

        $dependencyFactory->getMetadataStorage()->ensureInitialized();
        $migrator->migrate($plan, $migratorConfiguration);
    }
}

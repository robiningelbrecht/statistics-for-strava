<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226161339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE CacheTagDependency (entityType VARCHAR(255) NOT NULL, entityId VARCHAR(255) NOT NULL, dependsOnTag VARCHAR(255) NOT NULL, PRIMARY KEY (entityType, entityId, dependsOnTag))');
        $this->addSql('CREATE INDEX CacheTagDependency_dependsOnTag ON CacheTagDependency (dependsOnTag)');
        $this->addSql('CREATE TABLE InvalidatedCacheTag (tag VARCHAR(255) NOT NULL, PRIMARY KEY (tag))');
        $this->addSql('CREATE TABLE ContentHash (entityType VARCHAR(255) NOT NULL, entityId VARCHAR(255) NOT NULL, hash VARCHAR(255) NOT NULL, PRIMARY KEY (entityType, entityId))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

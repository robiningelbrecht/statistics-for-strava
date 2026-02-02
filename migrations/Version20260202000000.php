<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add indexes to improve query performance based on query analysis.
 */
final class Version20260202000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for Activity, Gear, Segment, SegmentEffort, and ChatMessage tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX Activity_gearId ON Activity (gearId)');
        $this->addSql('CREATE INDEX Activity_markedForDeletion ON Activity (markedForDeletion)');
        $this->addSql('CREATE INDEX Activity_streamsAreImported ON Activity (streamsAreImported)');
        $this->addSql('CREATE INDEX Gear_type ON Gear (type)');
        $this->addSql('CREATE INDEX Segment_detailsHaveBeenImported ON Segment (detailsHaveBeenImported)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentElapsedTime ON SegmentEffort (segmentId, elapsedTimeInSeconds)');
        $this->addSql('CREATE INDEX SegmentEffort_segmentStartDateTime ON SegmentEffort (segmentId, startDateTime)');
        $this->addSql('CREATE INDEX ChatMessage_on ON ChatMessage ("on")');
    }

    public function down(Schema $schema): void
    {
    }
}

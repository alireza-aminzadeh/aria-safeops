<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 2: PetroOps equipment holds for open anomalies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE petroops_equipment_holds (
    id UUID NOT NULL,
    equipment_tag VARCHAR(64) NOT NULL,
    event_id VARCHAR(64) NOT NULL,
    score DOUBLE PRECISION DEFAULT NULL,
    summary TEXT DEFAULT NULL,
    status VARCHAR(32) NOT NULL,
    detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    PRIMARY KEY (id)
)
SQL);
        $this->addSql('CREATE UNIQUE INDEX uniq_petroops_holds_tag ON petroops_equipment_holds (equipment_tag)');
        $this->addSql('CREATE INDEX idx_petroops_holds_status ON petroops_equipment_holds (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE petroops_equipment_holds');
    }
}

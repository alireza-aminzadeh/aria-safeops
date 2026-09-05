<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 1 completion: LOTO fields, work description, RCA notes, permit indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE permits ADD work_description TEXT DEFAULT '' NOT NULL");
        $this->addSql('ALTER TABLE permits ADD isolation_confirmed BOOLEAN DEFAULT false NOT NULL');
        $this->addSql("ALTER TABLE permits ADD isolation_points JSON DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE permits ADD isolation_confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE permits ADD isolation_confirmed_by UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE permits ADD CONSTRAINT FK_permits_isolation_user FOREIGN KEY (isolation_confirmed_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX idx_permits_status ON permits (status)');
        $this->addSql('CREATE INDEX idx_permits_equipment ON permits (equipment_tag)');
        $this->addSql('ALTER TABLE incidents ADD rca_notes TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE incidents DROP rca_notes');
        $this->addSql('ALTER TABLE permits DROP CONSTRAINT FK_permits_isolation_user');
        $this->addSql('DROP INDEX idx_permits_status');
        $this->addSql('DROP INDEX idx_permits_equipment');
        $this->addSql('ALTER TABLE permits DROP work_description');
        $this->addSql('ALTER TABLE permits DROP isolation_confirmed');
        $this->addSql('ALTER TABLE permits DROP isolation_points');
        $this->addSql('ALTER TABLE permits DROP isolation_confirmed_at');
        $this->addSql('ALTER TABLE permits DROP isolation_confirmed_by');
    }
}

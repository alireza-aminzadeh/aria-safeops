<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: incident safety KPI fields (LTIFR/TRIR) + structured RCA + safety_period_metrics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE incidents ADD root_cause_whys JSON DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE incidents ADD root_cause_category VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE incidents ADD lost_days INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE incidents ADD recordable BOOLEAN DEFAULT false NOT NULL');

        $this->addSql('CREATE TABLE safety_period_metrics (id UUID NOT NULL, tenant_id UUID NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, hours_worked NUMERIC(12, 2) NOT NULL, employee_count INT DEFAULT NULL, created_by UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE safety_period_metrics ADD CONSTRAINT FK_safety_metric_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE safety_period_metrics ADD CONSTRAINT FK_safety_metric_user FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX idx_safety_metric_tenant_period ON safety_period_metrics (tenant_id, period_start)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE safety_period_metrics');
        $this->addSql('ALTER TABLE incidents DROP root_cause_whys');
        $this->addSql('ALTER TABLE incidents DROP root_cause_category');
        $this->addSql('ALTER TABLE incidents DROP lost_days');
        $this->addSql('ALTER TABLE incidents DROP recordable');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: PSM audit (OSHA 1910.119 14-element checklist)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE psm_audits (id UUID NOT NULL, tenant_id UUID NOT NULL, title VARCHAR(180) NOT NULL, audit_date DATE NOT NULL, auditor_name VARCHAR(180) NOT NULL, status VARCHAR(32) NOT NULL, overall_score_percent DOUBLE PRECISION DEFAULT NULL, created_by UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE psm_audits ADD CONSTRAINT FK_psm_audit_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE psm_audits ADD CONSTRAINT FK_psm_audit_user FOREIGN KEY (created_by) REFERENCES users (id)');
        $this->addSql('CREATE INDEX idx_psm_audit_tenant ON psm_audits (tenant_id)');

        $this->addSql('CREATE TABLE psm_audit_findings (id UUID NOT NULL, audit_id UUID NOT NULL, element_code VARCHAR(64) NOT NULL, element_name_fa VARCHAR(180) NOT NULL, rating VARCHAR(32) NOT NULL, notes TEXT DEFAULT NULL, corrective_action TEXT DEFAULT NULL, due_date DATE DEFAULT NULL, status VARCHAR(32) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE psm_audit_findings ADD CONSTRAINT FK_psm_finding_audit FOREIGN KEY (audit_id) REFERENCES psm_audits (id)');
        $this->addSql('CREATE INDEX idx_psm_finding_audit ON psm_audit_findings (audit_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE psm_audit_findings');
        $this->addSql('DROP TABLE psm_audits');
    }
}

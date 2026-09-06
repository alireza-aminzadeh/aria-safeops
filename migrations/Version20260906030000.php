<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: Emergency Response & Environment module (ERP plans, drills, effluent readings)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE erp_plans (id UUID NOT NULL, tenant_id UUID NOT NULL, scenario_type VARCHAR(32) NOT NULL, title VARCHAR(180) NOT NULL, description TEXT NOT NULL, reviewed_at DATE DEFAULT NULL, next_review_due DATE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE erp_plans ADD CONSTRAINT FK_erp_plan_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('CREATE INDEX idx_erp_plan_tenant ON erp_plans (tenant_id)');

        $this->addSql('CREATE TABLE emergency_drills (id UUID NOT NULL, tenant_id UUID NOT NULL, erp_plan_id UUID DEFAULT NULL, scenario VARCHAR(180) NOT NULL, held_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, participant_count INT NOT NULL, duration_minutes INT NOT NULL, leader_name VARCHAR(180) NOT NULL, findings TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE emergency_drills ADD CONSTRAINT FK_drill_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE emergency_drills ADD CONSTRAINT FK_drill_plan FOREIGN KEY (erp_plan_id) REFERENCES erp_plans (id)');
        $this->addSql('CREATE INDEX idx_drill_tenant ON emergency_drills (tenant_id)');

        $this->addSql('CREATE TABLE effluent_readings (id UUID NOT NULL, tenant_id UUID NOT NULL, parameter VARCHAR(64) NOT NULL, value DOUBLE PRECISION NOT NULL, unit VARCHAR(16) NOT NULL, limit_value DOUBLE PRECISION DEFAULT NULL, location VARCHAR(120) NOT NULL, compliant BOOLEAN NOT NULL, sampled_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE effluent_readings ADD CONSTRAINT FK_effluent_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('CREATE INDEX idx_effluent_tenant ON effluent_readings (tenant_id)');
        $this->addSql('CREATE INDEX idx_effluent_compliant ON effluent_readings (compliant)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE effluent_readings');
        $this->addSql('DROP TABLE emergency_drills');
        $this->addSql('DROP TABLE erp_plans');
    }
}

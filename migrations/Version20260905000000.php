<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 1 SafeOps schema + Timescale extension';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS timescaledb');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pgcrypto');
        $this->addSql('CREATE TABLE tenants (id UUID NOT NULL, name VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, tenant_id UUID NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, roles JSON NOT NULL, full_name VARCHAR(180) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX users_email_unique ON users (email)');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('CREATE TABLE permit_types (id UUID NOT NULL, code VARCHAR(64) NOT NULL, name_fa VARCHAR(120) NOT NULL, name_en VARCHAR(120) NOT NULL, requires_gas_test BOOLEAN NOT NULL, requires_isolation BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_permit_types_code ON permit_types (code)');
        $this->addSql('CREATE TABLE permits (id UUID NOT NULL, tenant_id UUID NOT NULL, permit_type_id UUID NOT NULL, status VARCHAR(32) NOT NULL, equipment_tag VARCHAR(64) NOT NULL, location_plot_ref VARCHAR(120) DEFAULT NULL, requested_by UUID DEFAULT NULL, approved_by UUID DEFAULT NULL, valid_from TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, valid_to TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, jsa_reference JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE permits ADD CONSTRAINT FK_permits_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE permits ADD CONSTRAINT FK_permits_type FOREIGN KEY (permit_type_id) REFERENCES permit_types (id)');
        $this->addSql('ALTER TABLE permits ADD CONSTRAINT FK_permits_requested FOREIGN KEY (requested_by) REFERENCES users (id)');
        $this->addSql('ALTER TABLE permits ADD CONSTRAINT FK_permits_approved FOREIGN KEY (approved_by) REFERENCES users (id)');
        $this->addSql('CREATE TABLE gas_test_readings (id UUID NOT NULL, permit_id UUID NOT NULL, gas_type VARCHAR(16) NOT NULL, reading_value NUMERIC(10, 3) NOT NULL, recorded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, recorded_by UUID DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE gas_test_readings ADD CONSTRAINT FK_gas_permit FOREIGN KEY (permit_id) REFERENCES permits (id)');
        $this->addSql('ALTER TABLE gas_test_readings ADD CONSTRAINT FK_gas_user FOREIGN KEY (recorded_by) REFERENCES users (id)');
        $this->addSql('CREATE TABLE moc_requests (id UUID NOT NULL, tenant_id UUID NOT NULL, status VARCHAR(32) NOT NULL, change_type VARCHAR(32) NOT NULL, description TEXT NOT NULL, equipment_tag VARCHAR(64) DEFAULT NULL, requested_by UUID DEFAULT NULL, pssr_completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE moc_requests ADD CONSTRAINT FK_moc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE moc_requests ADD CONSTRAINT FK_moc_user FOREIGN KEY (requested_by) REFERENCES users (id)');
        $this->addSql('CREATE TABLE hazop_register_items (id UUID NOT NULL, moc_request_id UUID DEFAULT NULL, node_description TEXT NOT NULL, deviation VARCHAR(120) NOT NULL, cause TEXT NOT NULL, consequence TEXT NOT NULL, safeguards TEXT NOT NULL, risk_ranking VARCHAR(32) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE hazop_register_items ADD CONSTRAINT FK_hazop_moc FOREIGN KEY (moc_request_id) REFERENCES moc_requests (id)');
        $this->addSql('CREATE TABLE incidents (id UUID NOT NULL, tenant_id UUID NOT NULL, type VARCHAR(32) NOT NULL, severity VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, description TEXT NOT NULL, location VARCHAR(180) DEFAULT NULL, reported_by UUID DEFAULT NULL, reported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_inc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE incidents ADD CONSTRAINT FK_inc_user FOREIGN KEY (reported_by) REFERENCES users (id)');
        $this->addSql('CREATE TABLE capa_actions (id UUID NOT NULL, incident_id UUID NOT NULL, description TEXT NOT NULL, assigned_to UUID DEFAULT NULL, due_date DATE DEFAULT NULL, status VARCHAR(32) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE capa_actions ADD CONSTRAINT FK_capa_inc FOREIGN KEY (incident_id) REFERENCES incidents (id)');
        $this->addSql('ALTER TABLE capa_actions ADD CONSTRAINT FK_capa_user FOREIGN KEY (assigned_to) REFERENCES users (id)');
        $this->addSql('CREATE TABLE contractors (id UUID NOT NULL, company_name VARCHAR(180) NOT NULL, hse_prequalification_score NUMERIC(5, 2) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE contractor_certifications (id UUID NOT NULL, contractor_id UUID NOT NULL, type VARCHAR(64) NOT NULL, expires_at DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE contractor_certifications ADD CONSTRAINT FK_cert_contractor FOREIGN KEY (contractor_id) REFERENCES contractors (id)');
        $this->addSql('CREATE TABLE audit_log (id BIGSERIAL NOT NULL, tenant_id UUID NOT NULL, entity VARCHAR(64) NOT NULL, entity_id VARCHAR(64) NOT NULL, action VARCHAR(64) NOT NULL, actor_id UUID DEFAULT NULL, payload JSON NOT NULL, prev_hash VARCHAR(64) NOT NULL, hash VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE ai_query_log (id UUID NOT NULL, tenant_id UUID NOT NULL, user_id UUID DEFAULT NULL, query_text TEXT NOT NULL, response_text TEXT DEFAULT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE users ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE permits ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE moc_requests ENABLE ROW LEVEL SECURITY');
        $this->addSql('ALTER TABLE incidents ENABLE ROW LEVEL SECURITY');
        $this->addSql("CREATE POLICY tenant_isolation_users ON users USING (current_setting('app.tenant_id', true) IS NULL OR current_setting('app.tenant_id', true) = '' OR tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY tenant_isolation_permits ON permits USING (current_setting('app.tenant_id', true) IS NULL OR current_setting('app.tenant_id', true) = '' OR tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY tenant_isolation_moc ON moc_requests USING (current_setting('app.tenant_id', true) IS NULL OR current_setting('app.tenant_id', true) = '' OR tenant_id = current_setting('app.tenant_id')::uuid)");
        $this->addSql("CREATE POLICY tenant_isolation_incidents ON incidents USING (current_setting('app.tenant_id', true) IS NULL OR current_setting('app.tenant_id', true) = '' OR tenant_id = current_setting('app.tenant_id')::uuid)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ai_query_log');
        $this->addSql('DROP TABLE IF EXISTS audit_log');
        $this->addSql('DROP TABLE IF EXISTS contractor_certifications');
        $this->addSql('DROP TABLE IF EXISTS contractors');
        $this->addSql('DROP TABLE IF EXISTS capa_actions');
        $this->addSql('DROP TABLE IF EXISTS incidents');
        $this->addSql('DROP TABLE IF EXISTS hazop_register_items');
        $this->addSql('DROP TABLE IF EXISTS moc_requests');
        $this->addSql('DROP TABLE IF EXISTS gas_test_readings');
        $this->addSql('DROP TABLE IF EXISTS permits');
        $this->addSql('DROP TABLE IF EXISTS permit_types');
        $this->addSql('DROP TABLE IF EXISTS users');
        $this->addSql('DROP TABLE IF EXISTS tenants');
    }
}

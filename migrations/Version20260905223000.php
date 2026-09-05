<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 2: HAZOP/LOPA/Bowtie, shift logbook, vision, contractor training';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hazop_register_items ADD tenant_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE hazop_register_items ADD equipment_tag VARCHAR(64) DEFAULT NULL');
        $this->addSql("ALTER TABLE hazop_register_items ADD status VARCHAR(32) NOT NULL DEFAULT 'open'");
        $this->addSql('UPDATE hazop_register_items h SET tenant_id = m.tenant_id FROM moc_requests m WHERE h.moc_request_id = m.id');
        $this->addSql('ALTER TABLE hazop_register_items ADD CONSTRAINT FK_hazop_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('CREATE INDEX idx_hazop_tenant ON hazop_register_items (tenant_id)');

        $this->addSql('CREATE TABLE lopa_scenarios (id UUID NOT NULL, hazop_item_id UUID NOT NULL, initiating_event TEXT NOT NULL, ipl_count INT NOT NULL, target_frequency VARCHAR(64) NOT NULL, residual_risk VARCHAR(32) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE lopa_scenarios ADD CONSTRAINT FK_lopa_hazop FOREIGN KEY (hazop_item_id) REFERENCES hazop_register_items (id)');

        $this->addSql('CREATE TABLE bowtie_barriers (id UUID NOT NULL, hazop_item_id UUID NOT NULL, side VARCHAR(32) NOT NULL, description TEXT NOT NULL, effectiveness VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE bowtie_barriers ADD CONSTRAINT FK_bowtie_hazop FOREIGN KEY (hazop_item_id) REFERENCES hazop_register_items (id)');

        $this->addSql('CREATE TABLE shift_handovers (id UUID NOT NULL, tenant_id UUID NOT NULL, shift_date DATE NOT NULL, shift_name VARCHAR(32) NOT NULL, outgoing_name VARCHAR(180) NOT NULL, incoming_name VARCHAR(180) NOT NULL, summary TEXT NOT NULL, outstanding_work TEXT DEFAULT NULL, status VARCHAR(32) NOT NULL, created_by UUID DEFAULT NULL, accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE shift_handovers ADD CONSTRAINT FK_shift_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE shift_handovers ADD CONSTRAINT FK_shift_user FOREIGN KEY (created_by) REFERENCES users (id)');

        $this->addSql('CREATE TABLE logbook_entries (id UUID NOT NULL, tenant_id UUID NOT NULL, category VARCHAR(32) NOT NULL, body TEXT NOT NULL, author_name VARCHAR(180) NOT NULL, author_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE logbook_entries ADD CONSTRAINT FK_log_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');
        $this->addSql('ALTER TABLE logbook_entries ADD CONSTRAINT FK_log_user FOREIGN KEY (author_id) REFERENCES users (id)');

        $this->addSql('CREATE TABLE toolbox_talks (id UUID NOT NULL, tenant_id UUID NOT NULL, topic VARCHAR(180) NOT NULL, location VARCHAR(180) NOT NULL, held_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, leader_name VARCHAR(180) NOT NULL, attendee_count INT NOT NULL, notes TEXT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE toolbox_talks ADD CONSTRAINT FK_tbt_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');

        $this->addSql('CREATE TABLE contractor_training_records (id UUID NOT NULL, contractor_id UUID NOT NULL, course_code VARCHAR(64) NOT NULL, course_name VARCHAR(180) NOT NULL, completed_at DATE NOT NULL, expires_at DATE DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE contractor_training_records ADD CONSTRAINT FK_train_contractor FOREIGN KEY (contractor_id) REFERENCES contractors (id)');

        $this->addSql('CREATE TABLE vision_cameras (id UUID NOT NULL, tenant_id UUID NOT NULL, name VARCHAR(120) NOT NULL, area VARCHAR(120) NOT NULL, rtsp_url VARCHAR(255) DEFAULT NULL, enabled BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE vision_cameras ADD CONSTRAINT FK_cam_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)');

        $this->addSql('CREATE TABLE vision_events (id UUID NOT NULL, camera_id UUID NOT NULL, event_type VARCHAR(32) NOT NULL, confidence DOUBLE PRECISION NOT NULL, summary TEXT NOT NULL, status VARCHAR(32) NOT NULL, detected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE vision_events ADD CONSTRAINT FK_ve_camera FOREIGN KEY (camera_id) REFERENCES vision_cameras (id)');
        $this->addSql('CREATE INDEX idx_vision_events_detected ON vision_events (detected_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE vision_events');
        $this->addSql('DROP TABLE vision_cameras');
        $this->addSql('DROP TABLE contractor_training_records');
        $this->addSql('DROP TABLE toolbox_talks');
        $this->addSql('DROP TABLE logbook_entries');
        $this->addSql('DROP TABLE shift_handovers');
        $this->addSql('DROP TABLE bowtie_barriers');
        $this->addSql('DROP TABLE lopa_scenarios');
        $this->addSql('ALTER TABLE hazop_register_items DROP CONSTRAINT FK_hazop_tenant');
        $this->addSql('DROP INDEX idx_hazop_tenant');
        $this->addSql('ALTER TABLE hazop_register_items DROP tenant_id');
        $this->addSql('ALTER TABLE hazop_register_items DROP equipment_tag');
        $this->addSql('ALTER TABLE hazop_register_items DROP status');
    }
}

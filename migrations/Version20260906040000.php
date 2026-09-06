<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: electronic signatures on permit approve/activate and MOC approve';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE electronic_signatures (id UUID NOT NULL, tenant_id UUID NOT NULL, entity_type VARCHAR(32) NOT NULL, entity_id VARCHAR(64) NOT NULL, action VARCHAR(64) NOT NULL, signer_id UUID DEFAULT NULL, signer_name VARCHAR(180) NOT NULL, signed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, content_hash VARCHAR(64) NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_esign_entity ON electronic_signatures (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_esign_tenant ON electronic_signatures (tenant_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE electronic_signatures');
    }
}

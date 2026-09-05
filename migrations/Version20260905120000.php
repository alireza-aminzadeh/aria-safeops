<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique username for login (alireza operator account)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD username VARCHAR(64)');
        $this->addSql("UPDATE users SET username = split_part(email, '@', 1) WHERE username IS NULL OR username = ''");
        $this->addSql('ALTER TABLE users ALTER COLUMN username SET NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX users_username_unique ON users (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX users_username_unique');
        $this->addSql('ALTER TABLE users DROP username');
    }
}

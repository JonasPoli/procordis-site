<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Consolidated migration to create transparency and page tables and fix foreign keys.
 */
final class Version20251226000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create transparency and page tables, add FK to transparency_doc';
    }

    public function up(Schema $schema): void
    {
        // 1. Create transparency table
        $this->addSql('CREATE TABLE IF NOT EXISTS transparency (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_TRANSPARENCY_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // 2. Create page table
        $this->addSql('CREATE TABLE IF NOT EXISTS page (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            published_at DATETIME DEFAULT NULL,
            updated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_PAGE_SLUG (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // 3. Add transparency_id to transparency_doc
        // Using direct ALTER assuming strict migration order on fresh DB
        $this->addSql('ALTER TABLE transparency_doc ADD COLUMN transparency_id INT NOT NULL');

        // 4. Add FK
        $this->addSql('ALTER TABLE transparency_doc ADD CONSTRAINT FK_TRANSPARENCY_DOC_TRANSPARENCY FOREIGN KEY (transparency_id) REFERENCES transparency(id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transparency_doc DROP FOREIGN KEY FK_TRANSPARENCY_DOC_TRANSPARENCY');
        $this->addSql('ALTER TABLE transparency_doc DROP COLUMN transparency_id');
        $this->addSql('DROP TABLE IF EXISTS page');
        $this->addSql('DROP TABLE IF EXISTS transparency');
    }
}

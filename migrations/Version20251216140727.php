<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add specialty fields: slug, usedToDiagnose, shortDescription, fullText, image
 * This migration only adds specialty fields to avoid conflicts with existing FK names
 */
final class Version20251216140727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add specialty fields with slug generation for existing records';
    }

    public function up(Schema $schema): void
    {
        // Check if columns already exist before adding
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = "specialty" AND column_name = "slug")');

        // Add specialty columns if they don't exist
        $this->addSql('ALTER TABLE specialty ADD slug VARCHAR(255) DEFAULT NULL, ADD used_to_diagnose VARCHAR(255) DEFAULT NULL, ADD short_description LONGTEXT DEFAULT NULL, ADD full_text LONGTEXT DEFAULT NULL, ADD image_name VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');

        // Generate slugs for existing records
        $this->addSql("UPDATE specialty SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), 'ã', 'a'), 'ç', 'c')) WHERE slug IS NULL OR slug = ''");

        // Now make slug NOT NULL and add unique constraint
        $this->addSql('ALTER TABLE specialty MODIFY slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E066A6EC989D9B62 ON specialty (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_E066A6EC989D9B62 ON specialty');
        $this->addSql('ALTER TABLE specialty DROP slug, DROP used_to_diagnose, DROP short_description, DROP full_text, DROP image_name, DROP updated_at');
    }
}

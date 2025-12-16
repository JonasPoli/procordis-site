<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add specialty fields: slug, usedToDiagnose, shortDescription, fullText, image
 */
final class Version20251216140727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add specialty fields with slug generation for existing records';
    }

    public function up(Schema $schema): void
    {
        // Add columns first without unique constraint
        $this->addSql('ALTER TABLE specialty ADD slug VARCHAR(255) DEFAULT NULL, ADD used_to_diagnose VARCHAR(255) DEFAULT NULL, ADD short_description LONGTEXT DEFAULT NULL, ADD full_text LONGTEXT DEFAULT NULL, ADD image_name VARCHAR(255) DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');

        // Generate slugs for existing records
        $this->addSql("UPDATE specialty SET slug = LOWER(REPLACE(REPLACE(REPLACE(name, ' ', '-'), 'ã', 'a'), 'ç', 'c')) WHERE slug IS NULL");

        // Now make slug NOT NULL and add unique constraint
        $this->addSql('ALTER TABLE specialty MODIFY slug VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E066A6EC989D9B62 ON specialty (slug)');

        // Other migrations
        $this->addSql('ALTER TABLE news_news_category RENAME INDEX idx_news_news_category_news_id TO IDX_1A91D6D6B5A459A0');
        $this->addSql('ALTER TABLE news_news_category RENAME INDEX idx_news_news_category_category_id TO IDX_1A91D6D63B732BAD');
        $this->addSql('ALTER TABLE news_category CHANGE active active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE page CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE page RENAME INDEX uniq_page_slug TO UNIQ_140AB620989D9B62');
        $this->addSql('ALTER TABLE transparency CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE transparency RENAME INDEX uniq_transparency_slug TO UNIQ_F7E69B41989D9B62');
        $this->addSql('ALTER TABLE transparency_doc DROP FOREIGN KEY `FK_TRANSPARENCY_DOC_TRANSPARENCY`');
        $this->addSql('ALTER TABLE transparency_doc CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE transparency_doc ADD CONSTRAINT FK_3239BF02CCC536AC FOREIGN KEY (transparency_id) REFERENCES transparency (id)');
        $this->addSql('ALTER TABLE transparency_doc RENAME INDEX fk_transparency_doc_transparency TO IDX_3239BF02CCC536AC');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_category CHANGE active active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE news_news_category RENAME INDEX idx_1a91d6d63b732bad TO IDX_NEWS_NEWS_CATEGORY_CATEGORY_ID');
        $this->addSql('ALTER TABLE news_news_category RENAME INDEX idx_1a91d6d6b5a459a0 TO IDX_NEWS_NEWS_CATEGORY_NEWS_ID');
        $this->addSql('ALTER TABLE page CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE page RENAME INDEX uniq_140ab620989d9b62 TO UNIQ_PAGE_SLUG');
        $this->addSql('DROP INDEX UNIQ_E066A6EC989D9B62 ON specialty');
        $this->addSql('ALTER TABLE specialty DROP slug, DROP used_to_diagnose, DROP short_description, DROP full_text, DROP image_name, DROP updated_at');
        $this->addSql('ALTER TABLE transparency CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE transparency RENAME INDEX uniq_f7e69b41989d9b62 TO UNIQ_TRANSPARENCY_SLUG');
        $this->addSql('ALTER TABLE transparency_doc DROP FOREIGN KEY FK_3239BF02CCC536AC');
        $this->addSql('ALTER TABLE transparency_doc CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE transparency_doc ADD CONSTRAINT `FK_TRANSPARENCY_DOC_TRANSPARENCY` FOREIGN KEY (transparency_id) REFERENCES transparency (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transparency_doc RENAME INDEX idx_3239bf02ccc536ac TO FK_TRANSPARENCY_DOC_TRANSPARENCY');
    }
}

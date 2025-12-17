<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: creates `news_category` table and the many-to-many join table `news_category_news`.
 */
final class Version20251216000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news_category table and join table for News <-> NewsCategory many-to-many relation';
    }

    public function up(Schema $schema): void
    {
        // Create news_category table
        $this->addSql('CREATE TABLE IF NOT EXISTS news_category (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // Create join table news_news_category
        $this->addSql('CREATE TABLE IF NOT EXISTS news_news_category (
            news_id INT NOT NULL,
            news_category_id INT NOT NULL,
            INDEX IDX_NEWS_NEWS_CATEGORY_NEWS_ID (news_id),
            INDEX IDX_NEWS_NEWS_CATEGORY_CATEGORY_ID (news_category_id),
            PRIMARY KEY(news_id, news_category_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');

        // Add foreign keys
        $this->addSql('ALTER TABLE news_news_category ADD CONSTRAINT FK_NEWS_NEWS_CATEGORY_NEWS FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE news_news_category ADD CONSTRAINT FK_NEWS_NEWS_CATEGORY_CATEGORY FOREIGN KEY (news_category_id) REFERENCES news_category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop join table first due to foreign key constraints
        $this->addSql('DROP TABLE news_news_category');
        // Then drop news_category table
        $this->addSql('DROP TABLE news_category');
    }
}

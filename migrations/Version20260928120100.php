<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Galeria das notícias: lista única e ordenável de itens (imagem enviada ou vídeo do YouTube).
 */
final class Version20260928120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cria a tabela news_gallery_item (galeria de imagens e vídeos do YouTube das notícias)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE news_gallery_item (
            id INT AUTO_INCREMENT NOT NULL,
            news_id INT NOT NULL,
            type VARCHAR(16) NOT NULL,
            caption VARCHAR(500) DEFAULT NULL,
            position INT DEFAULT 0 NOT NULL,
            active TINYINT(1) DEFAULT 1 NOT NULL,
            youtube_id VARCHAR(11) DEFAULT NULL,
            files JSON NOT NULL,
            original_filename VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_6A4E6425B5A459A0 (news_id),
            INDEX idx_news_gallery_item_position (news_id, position),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE news_gallery_item ADD CONSTRAINT FK_6A4E6425B5A459A0 FOREIGN KEY (news_id) REFERENCES news (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news_gallery_item DROP FOREIGN KEY FK_6A4E6425B5A459A0');
        $this->addSql('DROP TABLE news_gallery_item');
    }
}

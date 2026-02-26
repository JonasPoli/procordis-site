<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260226003308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor ADD is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE transparency CHANGE is_active is_active TINYINT NOT NULL');
        $this->addSql('ALTER TABLE transparency RENAME INDEX uniq_transparency_slug TO UNIQ_F7E69B41989D9B62');
        $this->addSql('ALTER TABLE transparency_doc RENAME INDEX fk_3239bf02ccc536ac TO IDX_3239BF02CCC536AC');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor DROP is_active');
        $this->addSql('ALTER TABLE transparency CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE transparency RENAME INDEX uniq_f7e69b41989d9b62 TO UNIQ_TRANSPARENCY_SLUG');
        $this->addSql('ALTER TABLE transparency_doc RENAME INDEX idx_3239bf02ccc536ac TO FK_3239BF02CCC536AC');
    }
}

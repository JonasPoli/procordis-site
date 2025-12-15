<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251215152925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE about_page (id INT AUTO_INCREMENT NOT NULL, home_title VARCHAR(255) DEFAULT NULL, home_summary LONGTEXT DEFAULT NULL, home_image_name VARCHAR(255) DEFAULT NULL, main_title VARCHAR(255) DEFAULT NULL, main_content LONGTEXT DEFAULT NULL, vision LONGTEXT DEFAULT NULL, mission LONGTEXT DEFAULT NULL, `values` LONGTEXT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_message (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, message LONGTEXT NOT NULL, created_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE home_banner (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, subtitle VARCHAR(255) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, btn1_text VARCHAR(255) DEFAULT NULL, btn1_link VARCHAR(255) DEFAULT NULL, btn2_text VARCHAR(255) DEFAULT NULL, btn2_link VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, active TINYINT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE newsletter_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_401562C3E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE system_variable (id INT AUTO_INCREMENT NOT NULL, variable_key VARCHAR(255) NOT NULL, variable_value LONGTEXT DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_A5A7360FEBB76E63 (variable_key), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE timeline_item (id INT AUTO_INCREMENT NOT NULL, year VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, sort_order INT NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE about_page');
        $this->addSql('DROP TABLE contact_message');
        $this->addSql('DROP TABLE home_banner');
        $this->addSql('DROP TABLE newsletter_subscriber');
        $this->addSql('DROP TABLE system_variable');
        $this->addSql('DROP TABLE timeline_item');
    }
}

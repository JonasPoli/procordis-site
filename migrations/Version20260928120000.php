<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Campo "Ativo" nas notícias.
 *
 * Todas as notícias já existentes ficam ativas (DEFAULT 1 + UPDATE explícito), para que nada
 * suma do site no deploy. As categorias já tinham o campo "active" e ele é reutilizado sem
 * alteração de valores.
 */
final class Version20260928120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adiciona news.active (padrão ativo) e marca todas as notícias existentes como ativas';
    }

    public function up(Schema $schema): void
    {
        $columns = array_change_key_case($this->connection->createSchemaManager()->listTableColumns('news'), CASE_LOWER);

        if (!isset($columns['active'])) {
            $this->addSql('ALTER TABLE news ADD active TINYINT(1) DEFAULT 1 NOT NULL');
            // Garantia explícita: todas as notícias existentes continuam publicadas após o deploy.
            $this->addSql('UPDATE news SET active = 1');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE news DROP active');
    }
}

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
        return 'Add is_active to doctor and adjust transparency schema safely';
    }

    public function up(Schema $schema): void
    {
        $sm = $this->connection->createSchemaManager();

        $doctorColumns = array_change_key_case($sm->listTableColumns('doctor'), CASE_LOWER);
        if (!isset($doctorColumns['is_active'])) {
            $this->addSql('ALTER TABLE doctor ADD is_active TINYINT(1) DEFAULT 1 NOT NULL');
        }

        $transparencyColumns = array_change_key_case($sm->listTableColumns('transparency'), CASE_LOWER);
        if (isset($transparencyColumns['is_active'])) {
            $this->addSql('ALTER TABLE transparency CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        }

        $transparencyIndexes = array_change_key_case($sm->listTableIndexes('transparency'), CASE_LOWER);
        if (isset($transparencyIndexes['uniq_transparency_slug'])) {
            $this->addSql('ALTER TABLE transparency RENAME INDEX uniq_transparency_slug TO UNIQ_F7E69B41989D9B62');
        }

        $docIndexes = array_change_key_case($sm->listTableIndexes('transparency_doc'), CASE_LOWER);
        if (isset($docIndexes['fk_3239bf02ccc536ac'])) {
            $this->addSql('ALTER TABLE transparency_doc RENAME INDEX fk_3239bf02ccc536ac TO IDX_3239BF02CCC536AC');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE doctor DROP is_active');
    }
}

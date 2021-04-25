<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210801174540 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE commission ADD type VARCHAR(64) NOT NULL, ADD college VARCHAR(64) NOT NULL, ADD chat_link VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE dynamic_content DROP type');
        $this->addSql('ALTER TABLE job DROP min_shifter_alert');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE commission DROP type, DROP college, DROP chat_link');
        $this->addSql('ALTER TABLE dynamic_content ADD type VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'general\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE job ADD min_shifter_alert INT DEFAULT 2 NOT NULL');
    }
}

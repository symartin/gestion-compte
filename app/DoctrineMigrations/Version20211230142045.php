<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211230142045 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE shift_exchange (id INT AUTO_INCREMENT NOT NULL, shift_id INT DEFAULT NULL, receiver INT DEFAULT NULL, giver INT DEFAULT NULL, created_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_FA8F28DDBB70BC0E (shift_id), INDEX IDX_FA8F28DD3DB88C96 (receiver), INDEX IDX_FA8F28DD5DE37FD9 (giver), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB ROW_FORMAT = DYNAMIC');
        $this->addSql('ALTER TABLE shift_exchange ADD CONSTRAINT FK_FA8F28DDBB70BC0E FOREIGN KEY (shift_id) REFERENCES shift (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shift_exchange ADD CONSTRAINT FK_FA8F28DD3DB88C96 FOREIGN KEY (receiver) REFERENCES beneficiary (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shift_exchange ADD CONSTRAINT FK_FA8F28DD5DE37FD9 FOREIGN KEY (giver) REFERENCES beneficiary (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE shift_exchange');
    }
}

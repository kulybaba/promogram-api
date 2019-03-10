<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190304072111 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user ADD instagram_id BIGINT DEFAULT NULL, CHANGE email email VARCHAR(180) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499C19920F ON user (instagram_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865 ON user');
        $this->addSql('DROP INDEX UNIQ_8D93D6499C19920F ON user');
        $this->addSql('ALTER TABLE user DROP instagram_id, CHANGE email email VARCHAR(180) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}

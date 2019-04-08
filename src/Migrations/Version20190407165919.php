<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190407165919 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE coupon ADD count_likes INT NOT NULL, ADD percent_discount INT NOT NULL');
        $this->addSql('ALTER TABLE coupon RENAME INDEX idx_64bf3f022a1e4d73 TO IDX_64BF3F0223F5ED09');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE coupon DROP count_likes, DROP percent_discount');
        $this->addSql('ALTER TABLE coupon RENAME INDEX idx_64bf3f0223f5ed09 TO IDX_64BF3F022A1E4D73');
    }
}

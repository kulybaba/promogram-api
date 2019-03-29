<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190327083243 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F02A76ED395');
        $this->addSql('DROP INDEX IDX_64BF3F02A76ED395 ON coupon');
        $this->addSql('ALTER TABLE coupon CHANGE user_id retailer_id INT NOT NULL');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F022A1E4D73 FOREIGN KEY (retailer_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_64BF3F022A1E4D73 ON coupon (retailer_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE coupon DROP FOREIGN KEY FK_64BF3F022A1E4D73');
        $this->addSql('DROP INDEX IDX_64BF3F022A1E4D73 ON coupon');
        $this->addSql('ALTER TABLE coupon CHANGE retailer_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F02A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_64BF3F02A76ED395 ON coupon (user_id)');
    }
}

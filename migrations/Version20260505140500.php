<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add deliverer bag (cart) relations and quantities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart ADD deliverer_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_635E20D34C4A46FA ON cart (deliverer_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_635E20D34C4A46FA FOREIGN KEY (deliverer_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE cart_item ADD cart_id INT NOT NULL, ADD product_id INT NOT NULL, ADD quantity INT NOT NULL');
        $this->addSql('CREATE INDEX IDX_F0FE252E1AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IDX_F0FE252E4584665A ON cart_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_ITEM_CART_PRODUCT ON cart_item (cart_id, product_id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252E1AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252E1AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE252E4584665A');
        $this->addSql('DROP INDEX UNIQ_CART_ITEM_CART_PRODUCT ON cart_item');
        $this->addSql('DROP INDEX IDX_F0FE252E1AD5CDBF ON cart_item');
        $this->addSql('DROP INDEX IDX_F0FE252E4584665A ON cart_item');
        $this->addSql('ALTER TABLE cart_item DROP cart_id, DROP product_id, DROP quantity');

        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_635E20D34C4A46FA');
        $this->addSql('DROP INDEX UNIQ_635E20D34C4A46FA ON cart');
        $this->addSql('ALTER TABLE cart DROP deliverer_id');
    }
}


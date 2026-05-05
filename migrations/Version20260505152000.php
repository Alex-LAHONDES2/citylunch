<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505152000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename cart/cart_item tables to bag/bag_item to match entity naming';
    }

    public function up(Schema $schema): void
    {
        // Rename tables
        $this->addSql('RENAME TABLE cart TO bag');
        $this->addSql('RENAME TABLE cart_item TO bag_item');

        // Drop old FK/index names
        $this->addSql('ALTER TABLE bag_item DROP FOREIGN KEY FK_F0FE252E1AD5CDBF');
        $this->addSql('ALTER TABLE bag_item DROP FOREIGN KEY FK_F0FE252E4584665A');
        $this->addSql('ALTER TABLE bag DROP FOREIGN KEY FK_635E20D34C4A46FA');
        $this->addSql('DROP INDEX IDX_F0FE252E1AD5CDBF ON bag_item');
        $this->addSql('DROP INDEX IDX_F0FE252E4584665A ON bag_item');
        $this->addSql('DROP INDEX UNIQ_CART_ITEM_CART_PRODUCT ON bag_item');
        $this->addSql('DROP INDEX UNIQ_635E20D34C4A46FA ON bag');

        // Rename column cart_id -> bag_id in bag_item
        $this->addSql('ALTER TABLE bag_item CHANGE cart_id bag_id INT NOT NULL');

        // Recreate indexes/FKs with new names
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BAG_DELIVERER ON bag (deliverer_id)');
        $this->addSql('ALTER TABLE bag ADD CONSTRAINT FK_BAG_DELIVERER FOREIGN KEY (deliverer_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE INDEX IDX_BAG_ITEM_BAG ON bag_item (bag_id)');
        $this->addSql('CREATE INDEX IDX_BAG_ITEM_PRODUCT ON bag_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BAG_ITEM_BAG_PRODUCT ON bag_item (bag_id, product_id)');
        $this->addSql('ALTER TABLE bag_item ADD CONSTRAINT FK_BAG_ITEM_BAG FOREIGN KEY (bag_id) REFERENCES bag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bag_item ADD CONSTRAINT FK_BAG_ITEM_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Drop new constraints/indexes
        $this->addSql('ALTER TABLE bag_item DROP FOREIGN KEY FK_BAG_ITEM_BAG');
        $this->addSql('ALTER TABLE bag_item DROP FOREIGN KEY FK_BAG_ITEM_PRODUCT');
        $this->addSql('ALTER TABLE bag DROP FOREIGN KEY FK_BAG_DELIVERER');
        $this->addSql('DROP INDEX IDX_BAG_ITEM_BAG ON bag_item');
        $this->addSql('DROP INDEX IDX_BAG_ITEM_PRODUCT ON bag_item');
        $this->addSql('DROP INDEX UNIQ_BAG_ITEM_BAG_PRODUCT ON bag_item');
        $this->addSql('DROP INDEX UNIQ_BAG_DELIVERER ON bag');

        // Rename column bag_id -> cart_id
        $this->addSql('ALTER TABLE bag_item CHANGE bag_id cart_id INT NOT NULL');

        // Rename tables back
        $this->addSql('RENAME TABLE bag TO cart');
        $this->addSql('RENAME TABLE bag_item TO cart_item');

        // Recreate original indexes/FKs (names matching the original migration)
        $this->addSql('CREATE UNIQUE INDEX UNIQ_635E20D34C4A46FA ON cart (deliverer_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_635E20D34C4A46FA FOREIGN KEY (deliverer_id) REFERENCES user (id) ON DELETE CASCADE');

        $this->addSql('CREATE INDEX IDX_F0FE252E1AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IDX_F0FE252E4584665A ON cart_item (product_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CART_ITEM_CART_PRODUCT ON cart_item (cart_id, product_id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252E1AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE252E4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }
}

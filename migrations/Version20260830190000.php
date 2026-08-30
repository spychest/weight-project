<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830190000 extends AbstractMigration
{
    public function getDescription(): string { return 'Supprime automatiquement toutes les données du profil lors de la suppression du compte.'; }
    public function isTransactional(): bool { return false; }

    public function up(Schema $schema): void
    {
        $foreignKeys = [
            'activity' => 'FK_AC74095ACCFA12B8',
            'daily_checkin' => 'FK_3E82CD65CCFA12B8',
            'drink_entry' => 'FK_D5FD4CD5CCFA12B8',
            'food_event' => 'FK_CACFCF4DCCFA12B8',
            'milestone' => 'FK_4FAC8382CCFA12B8',
            'sleep_entry' => 'FK_B5D00BB0CCFA12B8',
            'victory' => 'FK_70BB005ACCFA12B8',
            'weight_entry' => 'FK_1486C8C0CCFA12B8',
        ];
        foreach ($foreignKeys as $table => $foreignKey) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $foreignKey));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (profile_id) REFERENCES profile (id) ON DELETE CASCADE', $table, $foreignKey));
        }
    }

    public function down(Schema $schema): void
    {
        $foreignKeys = [
            'activity' => 'FK_AC74095ACCFA12B8', 'daily_checkin' => 'FK_3E82CD65CCFA12B8',
            'drink_entry' => 'FK_D5FD4CD5CCFA12B8', 'food_event' => 'FK_CACFCF4DCCFA12B8',
            'milestone' => 'FK_4FAC8382CCFA12B8', 'sleep_entry' => 'FK_B5D00BB0CCFA12B8',
            'victory' => 'FK_70BB005ACCFA12B8', 'weight_entry' => 'FK_1486C8C0CCFA12B8',
        ];
        foreach ($foreignKeys as $table => $foreignKey) {
            $this->addSql(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $foreignKey));
            $this->addSql(sprintf('ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (profile_id) REFERENCES profile (id)', $table, $foreignKey));
        }
    }
}

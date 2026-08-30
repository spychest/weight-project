<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260830090000 extends AbstractMigration
{
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Ajoute des index composites pour accélérer les recherches par profil et par date.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_weight_entry_profile_date ON weight_entry (profile_id, measured_at)');
        $this->addSql('CREATE INDEX idx_drink_entry_profile_date ON drink_entry (profile_id, date)');
        $this->addSql('CREATE INDEX idx_food_event_profile_date ON food_event (profile_id, eaten_at)');
        $this->addSql('CREATE INDEX idx_daily_checkin_profile_date ON daily_checkin (profile_id, date)');
        $this->addSql('CREATE INDEX idx_sleep_entry_profile_date ON sleep_entry (profile_id, date)');
        $this->addSql('CREATE INDEX idx_activity_profile_date ON activity (profile_id, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_weight_entry_profile_date ON weight_entry');
        $this->addSql('DROP INDEX idx_drink_entry_profile_date ON drink_entry');
        $this->addSql('DROP INDEX idx_food_event_profile_date ON food_event');
        $this->addSql('DROP INDEX idx_daily_checkin_profile_date ON daily_checkin');
        $this->addSql('DROP INDEX idx_sleep_entry_profile_date ON sleep_entry');
        $this->addSql('DROP INDEX idx_activity_profile_date ON activity');
    }
}

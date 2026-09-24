<?php

namespace App\Service\Admin;

use Doctrine\DBAL\Connection;

final class AdminStatisticsService
{
    /** @var array<string, string> */
    private const COUNTED_TABLES = [
        'registeredUsers' => 'app_user',
        'profiles' => 'profile',
        'recipes' => 'recipe',
        'meals' => 'food_event',
        'weightEntries' => 'weight_entry',
        'drinkEntries' => 'drink_entry',
        'sleepEntries' => 'sleep_entry',
        'activities' => 'activity',
        'dailyCheckins' => 'daily_checkin',
        'shoppingLists' => 'shopping_list',
        'catalogItems' => 'ingredient_catalog_item',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, int> */
    public function getStatistics(): array
    {
        $statistics = [];
        foreach (self::COUNTED_TABLES as $statisticName => $tableName) {
            $statistics[$statisticName] = (int) $this->connection->fetchOne(
                sprintf('SELECT COUNT(*) FROM %s', $tableName),
            );
        }

        $statistics['activeShoppingLists'] = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM shopping_list WHERE active = 1',
        );
        $statistics['usersRegisteredLast30Days'] = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM app_user WHERE created_at >= :registrationDate',
            ['registrationDate' => (new \DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s')],
        );

        return $statistics;
    }
}

<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $connection = $schema->getConnection();

        $connection->table('settings')
            ->where('key', 'huseyinfiliz-leaderboard.leaderboard_name')
            ->where('value', 'Leaderboard')
            ->update(['value' => '排行榜']);

        $connection->table('settings')
            ->where('key', 'huseyinfiliz-leaderboard.points_label')
            ->where('value', 'Points')
            ->update(['value' => '积分']);
    },

    'down' => function (Builder $schema) {
    },
];

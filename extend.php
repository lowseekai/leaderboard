<?php

/*
 * This file is part of huseyinfiliz/leaderboard.
 *
 * Copyright (c) 2026 Huseyin Filiz.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace HuseyinFiliz\Leaderboard;

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/leaderboard', 'huseyinfiliz-leaderboard.index'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('api'))
        ->get('/leaderboard-entries', 'huseyinfiliz-leaderboard.api.index', Api\Controller\ListLeaderboardController::class),

    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('canViewLeaderboard')
                ->get(fn ($forum, Context $context) =>
                    $context->getActor()->hasPermission('huseyinfiliz-leaderboard.viewLeaderboard')
                ),
        ]),

    (new Extend\Settings())
        ->default('huseyinfiliz-leaderboard.leaderboard_name', '排行榜')
        ->default('huseyinfiliz-leaderboard.points_label', '积分')
        ->default('huseyinfiliz-leaderboard.excluded_groups', '[]')
        ->serializeToForum('huseyinfiliz-leaderboard.leaderboard_name', 'huseyinfiliz-leaderboard.leaderboard_name')
        ->serializeToForum('huseyinfiliz-leaderboard.points_label', 'huseyinfiliz-leaderboard.points_label'),
];

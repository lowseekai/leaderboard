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
use Flarum\Api\Endpoint;
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

    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Integer::make('leaderboardPoints')
                ->get(fn ($user) => $user->pointsBalance?->lifetime ?? 0),
        ])
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->eagerLoad(['pointsBalance']);
        })
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
            return $endpoint->eagerLoad(['pointsBalance']);
        }),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
            return $endpoint->eagerLoad([
                'user.pointsBalance',
                'lastPostedUser.pointsBalance',
                'mostRelevantPost.user.pointsBalance',
            ]);
        })
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->eagerLoad(['posts.user.pointsBalance']);
        }),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint(Endpoint\Index::class, function (Endpoint\Index $endpoint) {
            return $endpoint->eagerLoad(['user.pointsBalance']);
        })
        ->endpoint(Endpoint\Show::class, function (Endpoint\Show $endpoint) {
            return $endpoint->eagerLoad(['user.pointsBalance']);
        }),

    (new Extend\Settings())
        ->default('huseyinfiliz-leaderboard.leaderboard_name', 'Leaderboard')
        ->default('huseyinfiliz-leaderboard.points_label', 'Points')
        ->default('huseyinfiliz-leaderboard.excluded_groups', '[]')
        ->serializeToForum('huseyinfiliz-leaderboard.leaderboard_name', 'huseyinfiliz-leaderboard.leaderboard_name')
        ->serializeToForum('huseyinfiliz-leaderboard.points_label', 'huseyinfiliz-leaderboard.points_label'),
];

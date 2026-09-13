<?php

use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // Drop the child table first because it references users.
        $schema->dropIfExists('leaderboard_points');
        $schema->dropIfExists('leaderboard_user_totals');

        $schema->getConnection()
            ->table('settings')
            ->whereIn('key', [
                'huseyinfiliz-leaderboard.points_discussion_started',
                'huseyinfiliz-leaderboard.points_post_created',
                'huseyinfiliz-leaderboard.points_daily_login',
                'huseyinfiliz-leaderboard.points_like_received',
                'huseyinfiliz-leaderboard.points_like_given',
                'huseyinfiliz-leaderboard.points_reaction_received',
                'huseyinfiliz-leaderboard.points_reaction_given',
                'huseyinfiliz-leaderboard.points_best_answer',
                'huseyinfiliz-leaderboard.points_badge_earned',
                'huseyinfiliz-leaderboard.points_upvote_received',
                'huseyinfiliz-leaderboard.points_downvote_received',
                'huseyinfiliz-leaderboard.excluded_tags',
            ])
            ->delete();
    },

    // The legacy tables intentionally are not recreated on rollback. They are
    // no longer part of the extension's data model.
    'down' => function (Builder $schema) {
    },
];

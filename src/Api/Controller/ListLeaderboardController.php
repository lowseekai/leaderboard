<?php

namespace HuseyinFiliz\Leaderboard\Api\Controller;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use HuseyinFiliz\Leaderboard\Api\Data\LeaderboardEntryData;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ramon\PointSystem\Model\PointTransaction;
use Ramon\PointSystem\Model\UserPoints;

class ListLeaderboardController implements RequestHandlerInterface
{
    protected int $limit = 20;

    protected int $maxLimit = 50;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected SlugManager $slugManager
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);

        if (!$actor->hasPermission('huseyinfiliz-leaderboard.viewLeaderboard')) {
            throw new PermissionDeniedException();
        }

        $params = $request->getQueryParams();
        $filter = Arr::get($params, 'filter', []);
        $period = is_array($filter) ? Arr::get($filter, 'period', 'all') : 'all';
        $section = is_array($filter) ? Arr::get($filter, 'section', '') : '';
        $excludedGroupIds = $this->getExcludedGroupIds();

        switch ($section) {
            case 'podium':
                $offset = 0;
                $limit = 3;
                break;

            case 'contenders':
                $offset = 3;
                $limit = 7;
                break;

            case 'honorable':
                $sectionOffset = $this->extractOffset($params);
                $offset = 10 + $sectionOffset;
                $limit = $this->extractLimit($params);
                break;

            default:
                $offset = $this->extractOffset($params);
                $limit = $this->extractLimit($params);
                break;
        }

        if ($period === 'all') {
            $results = $this->getAllTimeResults($offset, $limit, $excludedGroupIds);
        } else {
            $results = $this->getPeriodResults(
                $this->getPeriodStart($period),
                $offset,
                $limit,
                $excludedGroupIds
            );
        }

        $data = [];
        $included = [];
        $seenUsers = [];

        foreach ($results['entries'] as $entry) {
            $entryData = [
                'type' => 'leaderboard-entries',
                'id' => (string) $entry->id,
                'attributes' => [
                    'points' => $entry->points,
                    'rank' => $entry->rank,
                ],
            ];

            if ($entry->user) {
                $entryData['relationships'] = [
                    'user' => [
                        'data' => ['type' => 'users', 'id' => (string) $entry->user->id],
                    ],
                ];

                if (!isset($seenUsers[$entry->user->id])) {
                    $included[] = $this->serializeUser($entry->user);
                    $seenUsers[$entry->user->id] = true;
                }
            }

            $data[] = $entryData;
        }

        $response = ['data' => $data];

        if (!empty($included)) {
            $response['included'] = $included;
        }

        if ($section === 'honorable') {
            $sectionOffset = $this->extractOffset($params);
            $hasMore = $results['total'] > $offset + count($data);
            $response['links'] = $this->buildPaginationLinks($request, $sectionOffset, $limit, $hasMore);
        } elseif ($section === '') {
            $hasMore = $results['total'] > $offset + count($data);
            $response['links'] = $this->buildPaginationLinks($request, $offset, $limit, $hasMore);
        }

        return new JsonResponse($response);
    }

    protected function serializeUser(User $user): array
    {
        $attributes = [
            'username' => $user->username,
            'displayName' => $user->display_name,
            'slug' => $this->slugManager->forResource(User::class)->toSlug($user),
        ];

        if ($user->avatar_url) {
            $attributes['avatarUrl'] = $user->avatar_url;
        }

        if ($user->comment_count !== null) {
            $attributes['commentCount'] = (int) $user->comment_count;
        }

        if ($user->discussion_count !== null) {
            $attributes['discussionCount'] = (int) $user->discussion_count;
        }

        return [
            'type' => 'users',
            'id' => (string) $user->id,
            'attributes' => $attributes,
        ];
    }

    protected function buildPaginationLinks(
        ServerRequestInterface $request,
        int $offset,
        int $limit,
        bool $hasMore
    ): array {
        $links = [];
        $baseUrl = $this->url->to('api')->route('huseyinfiliz-leaderboard.api.index');
        $queryParams = $request->getQueryParams();

        if ($offset > 0) {
            $firstParams = $queryParams;
            $firstParams['page'] = ['offset' => 0];
            $links['first'] = $baseUrl.'?'.http_build_query($firstParams, '', '&', PHP_QUERY_RFC3986);

            $prevOffset = max(0, $offset - $limit);
            $prevParams = $queryParams;
            $prevParams['page'] = ['offset' => $prevOffset];
            $links['prev'] = $baseUrl.'?'.http_build_query($prevParams, '', '&', PHP_QUERY_RFC3986);
        }

        if ($hasMore) {
            $nextParams = $queryParams;
            $nextParams['page'] = ['offset' => $offset + $limit];
            $links['next'] = $baseUrl.'?'.http_build_query($nextParams, '', '&', PHP_QUERY_RFC3986);
        }

        return $links;
    }

    protected function extractOffset(array $params): int
    {
        $page = Arr::get($params, 'page', []);

        return max(0, (int) Arr::get($page, 'offset', 0));
    }

    protected function extractLimit(array $params): int
    {
        $page = Arr::get($params, 'page', []);
        $limit = (int) Arr::get($page, 'limit', $this->limit);

        return max(1, min($limit, $this->maxLimit));
    }

    /**
     * All-time rankings use the authoritative lifetime value from Point System.
     */
    protected function getAllTimeResults(int $offset, int $limit, array $excludedGroupIds): array
    {
        $query = UserPoints::query()
            ->where('lifetime', '>', 0)
            ->orderByDesc('lifetime')
            ->orderBy('user_id');

        $this->applyGroupExclusion($query, $excludedGroupIds);

        $total = $query->count();
        $rows = $query->offset($offset)->limit($limit)->get();

        $userIds = $rows->pluck('user_id')->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $entries = [];

        foreach ($rows as $index => $row) {
            $user = $users->get($row->user_id);
            if (!$user) {
                continue;
            }

            $entries[] = new LeaderboardEntryData(
                (int) $user->id,
                (int) $row->lifetime,
                $offset + $index + 1,
                $user
            );
        }

        return ['entries' => $entries, 'total' => $total];
    }

    /**
     * Period rankings sum Point System's transaction ledger, including
     * negative reversal transactions.
     */
    protected function getPeriodResults(
        Carbon $periodStart,
        int $offset,
        int $limit,
        array $excludedGroupIds
    ): array {
        $query = PointTransaction::query()
            ->selectRaw('user_id, SUM(amount) as period_points')
            ->where('created_at', '>=', $periodStart)
            ->groupBy('user_id')
            ->havingRaw('SUM(amount) > 0')
            ->orderByDesc('period_points')
            ->orderBy('user_id');

        $this->applyGroupExclusion($query, $excludedGroupIds);

        $countQuery = PointTransaction::query()
            ->selectRaw('user_id')
            ->where('created_at', '>=', $periodStart)
            ->groupBy('user_id')
            ->havingRaw('SUM(amount) > 0');

        $this->applyGroupExclusion($countQuery, $excludedGroupIds);

        $total = $countQuery->getQuery()->getCountForPagination(['user_id']);
        $rows = $query->offset($offset)->limit($limit)->get();

        $userIds = $rows->pluck('user_id')->all();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $entries = [];

        foreach ($rows as $index => $row) {
            $user = $users->get($row->user_id);
            if (!$user) {
                continue;
            }

            $entries[] = new LeaderboardEntryData(
                (int) $user->id,
                (int) $row->period_points,
                $offset + $index + 1,
                $user
            );
        }

        return ['entries' => $entries, 'total' => $total];
    }

    protected function getPeriodStart(string $period): Carbon
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => $now->copy()->startOfDay(),
            'weekly' => $now->copy()->startOfWeek(Carbon::MONDAY),
            'monthly' => $now->copy()->startOfMonth(),
            'quarterly' => $now->copy()->firstOfQuarter(),
            'yearly' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfDay(),
        };
    }

    protected function getExcludedGroupIds(): array
    {
        $value = $this->settings->get('huseyinfiliz-leaderboard.excluded_groups', '[]');

        if (empty($value)) {
            return [];
        }

        $decoded = is_array($value) ? $value : json_decode($value, true);

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    protected function applyGroupExclusion($query, array $excludedGroupIds): void
    {
        if (empty($excludedGroupIds)) {
            return;
        }

        $query->whereNotIn('user_id', function ($sub) use ($excludedGroupIds) {
            $sub->select('user_id')
                ->from('group_user')
                ->whereIn('group_id', $excludedGroupIds);
        });
    }
}

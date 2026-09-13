<?php

namespace HuseyinFiliz\Leaderboard\Tests\Unit;

use Carbon\Carbon;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use HuseyinFiliz\Leaderboard\Api\Controller\ListLeaderboardController;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ListLeaderboardControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    #[Test]
    public function excluded_group_ids_are_decoded_and_cast_to_integers(): void
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')
            ->with('huseyinfiliz-leaderboard.excluded_groups', '[]')
            ->once()
            ->andReturn('["3", 4]');

        $controller = new ListLeaderboardController(
            $settings,
            m::mock(UrlGenerator::class),
            m::mock(SlugManager::class)
        );

        $method = new ReflectionMethod($controller, 'getExcludedGroupIds');

        $this->assertSame([3, 4], $method->invoke($controller));
    }

    #[Test]
    public function malformed_excluded_group_setting_is_treated_as_empty(): void
    {
        $settings = m::mock(SettingsRepositoryInterface::class);
        $settings->shouldReceive('get')
            ->with('huseyinfiliz-leaderboard.excluded_groups', '[]')
            ->once()
            ->andReturn('{invalid');

        $controller = new ListLeaderboardController(
            $settings,
            m::mock(UrlGenerator::class),
            m::mock(SlugManager::class)
        );

        $method = new ReflectionMethod($controller, 'getExcludedGroupIds');

        $this->assertSame([], $method->invoke($controller));
    }

    #[Test]
    public function monthly_period_starts_at_the_first_day_of_the_current_month(): void
    {
        $controller = new ListLeaderboardController(
            m::mock(SettingsRepositoryInterface::class),
            m::mock(UrlGenerator::class),
            m::mock(SlugManager::class)
        );

        $method = new ReflectionMethod($controller, 'getPeriodStart');
        $periodStart = $method->invoke($controller, 'monthly');

        $this->assertInstanceOf(Carbon::class, $periodStart);
        $this->assertTrue($periodStart->isStartOfMonth());
    }
}

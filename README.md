# Leaderboard

A Flarum 2 leaderboard powered by [Point System](https://github.com/lowseekai/point-system).
This extension is read-only with regard to points: it never awards, revokes, or
recalculates point data.

## Features

- All-time ranking from `point_system_user_points.lifetime`
- Daily, weekly, monthly, quarterly, and yearly ranking from
  `point_system_transactions`
- Podium, contenders, and honorable mentions views
- Configurable leaderboard name and point label
- Optional exclusion of users by group
- User-card display of the lifetime point total
- Permission control for viewing the leaderboard

## Installation

Install and enable Point System first:

```bash
composer config repositories.point-system vcs https://github.com/lowseekai/point-system.git
composer require ramon/point-system:dev-main
```

Then install this extension:

```bash
composer config repositories.lowseekai-leaderboard vcs https://github.com/lowseekai/leaderboard.git
composer require huseyinfiliz/leaderboard:dev-point-system-integration
php flarum migrate
php flarum cache:clear
```

The exact branch name may change. Use the branch selected for the release.

## Point Data

Point System is the only source of point mutations. Its automatic awards and
manual awards are reflected in the leaderboard automatically.

All-time rankings use each user's `lifetime` value. Period rankings sum
transactions created from the beginning of the selected calendar period. Both
positive and negative transactions are included; users whose period total is
not positive are omitted.

Point amounts, automatic awards, and transaction reasons are configured in
Point System. The `Points Label` setting here only changes the text displayed
next to a number.

## Updating

```bash
composer update ramon/point-system huseyinfiliz/leaderboard
php flarum migrate
php flarum cache:clear
```

When updating Point System, run its migrations before opening the leaderboard.

## License

MIT License. See [LICENSE.md](LICENSE.md).

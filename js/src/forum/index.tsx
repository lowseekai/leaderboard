import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import LinkButton from 'flarum/common/components/LinkButton';

import LeaderboardPage from './components/LeaderboardPage';

export { default as extend } from '../common/extend';

app.initializers.add('huseyinfiliz/leaderboard', () => {
  app.routes['huseyinfiliz-leaderboard.index'] = {
    path: '/leaderboard',
    component: LeaderboardPage,
  };

  // Add sidebar nav link
  extend(IndexSidebar.prototype, 'navItems', function (items) {
    if (!app.forum.attribute('canViewLeaderboard')) return;

    const leaderboardName = app.forum.attribute('huseyinfiliz-leaderboard.leaderboard_name') || '排行榜';

    items.add(
      'huseyinfiliz-leaderboard',
      <LinkButton href={app.route('huseyinfiliz-leaderboard.index')} icon="fas fa-trophy">
        {leaderboardName}
      </LinkButton>,
      10
    );
  });
});

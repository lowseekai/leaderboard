import Form from 'flarum/common/components/Form';
import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import GroupBadge from 'flarum/common/components/GroupBadge';
import type Group from 'flarum/common/models/Group';
import type Mithril from 'mithril';

import SelectGroupsModal from './SelectGroupsModal';

export default class LeaderboardSettingsPage extends ExtensionPage {
  private activeTab: 'general' | 'exclusions' = 'general';
  private selectedGroupIds: number[] = [];

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);
    this.initGroupSelection();
  }

  initGroupSelection() {
    const value = this.setting('huseyinfiliz-leaderboard.excluded_groups')();
    let ids: number[] = [];

    try {
      ids = JSON.parse(value || '[]');
    } catch (e) {
      // Keep the default selection when a legacy value is malformed.
    }

    this.selectedGroupIds = Array.isArray(ids) ? ids.map(Number) : [];
  }

  content() {
    return (
      <div className="LeaderboardSettings">
        <div className="LeaderboardSettings-header">
          <div className="LeaderboardSettings-tabs">
            {this.tabButton('general', 'fas fa-cog', 'huseyinfiliz-leaderboard.admin.tabs.general')}
            {this.tabButton('exclusions', 'fas fa-ban', 'huseyinfiliz-leaderboard.admin.tabs.exclusions')}
          </div>
        </div>

        <div className="LeaderboardSettings-content">
          {this.activeTab === 'general' && this.generalTab()}
          {this.activeTab === 'exclusions' && this.exclusionsTab()}
        </div>
      </div>
    );
  }

  tabButton(tab: 'general' | 'exclusions', iconClass: string, labelKey: string): Mithril.Children {
    return (
      <Button
        className={'Button ' + (this.activeTab === tab ? 'Button--primary' : '')}
        icon={iconClass}
        onclick={() => {
          this.activeTab = tab;
        }}
      >
        {app.translator.trans(labelKey)}
      </Button>
    );
  }

  generalTab(): Mithril.Children {
    return (
      <Form>
        <div className="Form-group">
          <label>{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.leaderboard_name_label')}</label>
          <input className="FormControl" bidi={this.setting('huseyinfiliz-leaderboard.leaderboard_name')} placeholder="Leaderboard" />
        </div>
        <div className="Form-group">
          <label>{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.points_label_label')}</label>
          <input className="FormControl" bidi={this.setting('huseyinfiliz-leaderboard.points_label')} placeholder="Points" />
          <p className="helpText">{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.points_label_help')}</p>
        </div>
        <div className="Form-group">
          <p className="helpText">{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.point_system_notice')}</p>
        </div>
        <div className="Form-group">{this.submitButton()}</div>
      </Form>
    );
  }

  exclusionsTab(): Mithril.Children {
    const groups = app.store.all<Group>('groups');
    const selectedGroups = groups.filter((g) => this.selectedGroupIds.includes(Number(g.id())));

    return (
      <Form>
        <div className="Form-group">
          <label>{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.excluded_groups_label')}</label>
          <p className="helpText">{app.translator.trans('huseyinfiliz-leaderboard.admin.settings.excluded_groups_help')}</p>
          <div className="LeaderboardSettings-selectedItems">
            {selectedGroups.length > 0 ? (
              selectedGroups.map((group) => (
                <span className="LeaderboardSettings-badge" key={group.id()}>
                  <GroupBadge group={group} label={null} /> {group.nameSingular()}
                </span>
              ))
            ) : (
              <span className="LeaderboardSettings-none">{app.translator.trans('huseyinfiliz-leaderboard.admin.modals.none_selected')}</span>
            )}
          </div>
          <Button
            className="Button"
            icon="fas fa-users"
            onclick={() => {
              app.modal.show(SelectGroupsModal, {
                selectedGroupIds: this.selectedGroupIds,
                onsubmit: (ids: number[]) => {
                  this.selectedGroupIds = ids;
                  this.setting('huseyinfiliz-leaderboard.excluded_groups')(JSON.stringify(ids));
                },
              });
            }}
          >
            {app.translator.trans('huseyinfiliz-leaderboard.admin.modals.select_groups')}
          </Button>
        </div>
        <div className="Form-group">{this.submitButton()}</div>
      </Form>
    );
  }
}

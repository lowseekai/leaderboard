import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
export default class LeaderboardSettingsPage extends ExtensionPage {
    private activeTab;
    private selectedGroupIds;
    oninit(vnode: Mithril.Vnode): void;
    initGroupSelection(): void;
    content(): JSX.Element;
    tabButton(tab: 'general' | 'exclusions', iconClass: string, labelKey: string): Mithril.Children;
    generalTab(): Mithril.Children;
    exclusionsTab(): Mithril.Children;
}

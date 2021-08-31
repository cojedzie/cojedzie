/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import { Options, Vue } from "vue-class-component";
import store, { MessagesSettings } from "../../store";
import { MessagesSettingsState } from "@/store/modules/settings/messages";
import { render } from "@templates/settings/messages.html";

@Options({ render, store })
export class SettingsMessages extends Vue {
    @MessagesSettings.State
    public autorefresh: boolean;

    @MessagesSettings.State
    public autorefreshInterval: number;

    @MessagesSettings.State
    public displayedEntriesCount: number;

    @MessagesSettings.Mutation
    public update: (state: Partial<MessagesSettingsState>) => void;
}


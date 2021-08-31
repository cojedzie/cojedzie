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

import { Message } from "@/model/message";
import store, { Messages, MessagesSettings } from '../store'
import { Options, Vue } from "vue-class-component";

@Options({ render: require("@templates/messages.html").render, store })
export class MessagesComponent extends Vue {
    @Messages.State('messages')
    public allMessages: Message[];

    @MessagesSettings.State
    public displayedEntriesCount: number;

    public showAll: boolean = false;

    get messages() {
        return this.showAll
            ? this.allMessages
            : this.allMessages.slice(0, this.displayedEntriesCount);
    }

    get nonDisplayedCount(): number {
        return Math.max(this.allMessages.length - this.displayedEntriesCount, 0);
    }

    public type(message: Message) {
        switch (message.type) {
            case "breakdown": return "danger";
            case "info": return "info";
            case "unknown": return "warning";
        }
    }
}

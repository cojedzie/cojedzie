import Vue from 'vue';
import { Component } from "vue-property-decorator";
import { Message } from "@/model/message";
import store, { Messages, MessagesSettings } from '../store'

@Component({ template: require("@templates/messages.html"), store })
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

Vue.component('Messages', MessagesComponent);

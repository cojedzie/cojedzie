<template>
    <div class="messages mb-2">
        <ul class="list-unstyled mb-0">
            <li v-for="message in messages" class="message alert" :class="`alert-${type(message)}`">
                <ui-icon :icon="`message-${message.type}`" fixed-width />
                {{ message.message }}

                <div class="message__info">
                    <small class="message__date">
                        Komunikat ważny od
                        {{ message.validFrom.format('HH:mm') }}
                        do
                        {{ message.validTo.format('HH:mm') }}
                    </small>
                </div>
            </li>
        </ul>
        <template v-if="nonDisplayedCount > 0">
            <div class="flex">
                <button class="btn btn-action btn-sm flex-space-left" @click="showAll = !showAll">
                    <template v-if="showAll">
                        <ui-icon icon="chevron-up" /> {{ nonDisplayedCount }} mniej
                    </template>
                    <template v-else>
                        <ui-icon icon="chevron-down" /> {{ nonDisplayedCount }} więcej
                    </template>
                </button>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import store, { Messages, MessagesSettings } from "@/store";
import { Message } from "@/model/message";

@Options({
    name: "MessagesList",
    store
})
export default class MessagesList extends Vue {
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
            case "breakdown":
                return "danger";
            case "info":
                return "info";
            case "unknown":
                return "warning";
        }
    }
}
</script>

<template>
    <div class="messages mb-2">
        <ul class="messages__list">
            <messages-message
                v-for="message in messages"
                :key="message.id"
                :message="message.message"
                :valid-from="message.validFrom"
                :valid-to="message.validTo"
                :type="message.type"
                :lines="message.$refs.lines.items"
                :stops="message.$refs.stops.items"
                tag="li"
            />
        </ul>
        <template v-if="nonDisplayedCount > 0">
            <div class="flex">
                <button class="btn btn-action btn-sm flex-space-left" @click="showAll = !showAll">
                    <template v-if="showAll"> <ui-icon icon="chevron-up" /> {{ nonDisplayedCount }} mniej </template>
                    <template v-else> <ui-icon icon="chevron-down" /> {{ nonDisplayedCount }} więcej </template>
                </button>
            </div>
        </template>
    </div>
</template>

<script lang="ts">
import { Options, Vue } from "vue-class-component";
import store, { Messages, MessagesSettings } from "@/store";
import { Message } from "@/model/message";
import MessagesMessage from "@/components/messages/MessagesMessage.vue";

@Options({
    name: "MessagesList",
    components: { MessagesMessage },
    store,
})
export default class MessagesList extends Vue {
    @Messages.State("messages")
    public allMessages: Message[];

    @MessagesSettings.State
    public displayedEntriesCount: number;

    public showAll: boolean = false;

    get messages() {
        return this.showAll ? this.allMessages : this.allMessages.slice(0, this.displayedEntriesCount);
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

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
            <div class="hstack">
                <button class="btn btn-action btn-sm flex-space-left" @click="showAll = !showAll">
                    <template v-if="showAll"> <ui-icon icon="chevron-up" /> {{ nonDisplayedCount }} mniej </template>
                    <template v-else> <ui-icon icon="chevron-down" /> {{ nonDisplayedCount }} więcej </template>
                </button>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { storeToRefs } from "pinia";
import { useMessagesStore } from "@/stores/messages";
import { useMessagesSettingsStore } from "@/stores/messages-settings";
import MessagesMessage from "./MessagesMessage.vue";
import UiIcon from "@/components/ui/UiIcon.vue";

defineOptions({ name: "MessagesList" });

const { messages: allMessages } = storeToRefs(useMessagesStore());
const { displayedEntriesCount } = storeToRefs(useMessagesSettingsStore());

const showAll = ref(false);

const messages = computed(() =>
    showAll.value ? allMessages.value : allMessages.value.slice(0, displayedEntriesCount.value),
);
const nonDisplayedCount = computed(() => Math.max(allMessages.value.length - displayedEntriesCount.value, 0));
</script>

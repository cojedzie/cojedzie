<template>
    <component :is="tag" class="message" :class="[`message--${type}`]">
        <ui-icon :icon="`message-${type}`" class="message__icon" />
        <div class="message__stops">
            <ul v-if="stops.length > 0" class="stop-list stop-list--inline">
                <li v-for="stop in stops" :key="stop.id">
                    <stop-label :stop="stop" />
                </li>
            </ul>
        </div>
        <div v-if="lines.length > 0" class="message__lines">
            <ul>
                <li v-for="line in lines" :key="line.id">
                    <line-symbol :line="line" />
                </li>
            </ul>
        </div>
        <p class="message__message">
            {{ message }}
        </p>
        <div v-if="validFrom || validTo" class="message__validity">
            ważne
            <template v-if="validFrom">
                od
                {{
                    validFrom.isSame(today, "day")
                        ? validFrom.isSame(validTo)
                            ? validFrom.calendar("LT")
                            : validFrom.calendar().toLowerCase()
                        : validFrom.format("LL")
                }}
            </template>
            <template v-if="validTo">
                do
                {{
                    validTo.isSame(today, "day")
                        ? validTo.isSame(validFrom)
                            ? validTo.calendar("LT")
                            : validTo.calendar().toLowerCase()
                        : validTo.format("LL")
                }}
            </template>
        </div>
    </component>
</template>

<script setup lang="ts">
import moment, { type Moment } from "moment";
import type { Line, Stop } from "@/model";
import type { MessageType } from "@/model/message";

defineOptions({ name: "MessagesMessage" });

withDefaults(
    defineProps<{
        message: string;
        lines?: Line[];
        stops?: Stop[];
        type?: MessageType;
        validFrom?: Moment | null;
        validTo?: Moment | null;
        tag?: string;
    }>(),
    {
        lines: () => [],
        stops: () => [],
        type: "unknown",
        validFrom: null,
        validTo: null,
        tag: "div",
    },
);

const today = moment();
</script>

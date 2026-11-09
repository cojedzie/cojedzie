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
                {{ validity(validFrom) }}
            </template>
            <template v-if="validTo">
                do
                {{ validity(validTo, validFrom) }}
            </template>
        </div>
    </component>
</template>

<script setup lang="ts">
import { useDisplayTimeZone } from "@/composables/useDisplayTimeZone";
import { dateIn, formatClockTime, formatDateInWords, Temporal } from "@/time";
import type { Line, Stop } from "@/model";
import type { MessageType } from "@/model/message";
import UiIcon from "@/components/ui/UiIcon.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";

defineOptions({ name: "MessagesMessage" });

withDefaults(
    defineProps<{
        message: string;
        lines?: Line[];
        stops?: Stop[];
        type?: MessageType;
        validFrom?: Temporal.Instant | null;
        validTo?: Temporal.Instant | null;
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

const timeZone = useDisplayTimeZone();

/**
 * One end of the validity: "dziś o 14:05" today, only the time when the other end is earlier the same day ("od dziś
 * o 10:00 do 14:00"), the date in words on other days. "Today" is taken in the zone the time is shown in, so the two
 * agree.
 */
function validity(end: Temporal.Instant, start?: Temporal.Instant | null): string {
    const day = dateIn(end, timeZone.value);
    const time = formatClockTime(end, timeZone.value);

    if (!day.equals(dateIn(Temporal.Now.instant(), timeZone.value))) {
        return formatDateInWords(day);
    }

    return start && dateIn(start, timeZone.value).equals(day) ? time : `dziś o ${time}`;
}
</script>

<template>
    <component :is="tag" class="message" :class="type">
        <ui-icon :icon="`message-${type}`" class="icon-type" />
        <div class="stops">
            <ul v-if="stops.length > 0" class="stop-list">
                <li v-for="stop in stops" :key="stop.id">
                    <stop-label :stop="stop" />
                </li>
            </ul>
        </div>
        <div v-if="lines.length > 0" class="lines">
            <ul>
                <li v-for="line in lines" :key="line.id">
                    <line-symbol :line="line" />
                </li>
            </ul>
        </div>
        <p class="text">
            {{ message }}
        </p>
        <div v-if="validFrom || validTo" class="validity">
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
import { dateIn, formatClockTime, formatDateInWords, formatDayAndTime, Temporal } from "@/time";
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
 * One end of the validity: "dzisiaj o 10:00" today, only the time when the other end is earlier the same day ("od
 * dzisiaj o 10:00 do 14:00"), the date in words on other days. Only today gets a day word: after "od" and "do" Polish
 * needs "jutra" and "wczoraj" in another case than Intl gives them ("do jutro"); translating whole sentences (#180)
 * would solve that.
 */
function validity(end: Temporal.Instant, start?: Temporal.Instant | null): string {
    const now = Temporal.Now.instant();
    const day = dateIn(end, timeZone.value);

    if (!day.equals(dateIn(now, timeZone.value))) {
        return formatDateInWords(day);
    }

    return start && dateIn(start, timeZone.value).equals(day)
        ? formatClockTime(end, timeZone.value)
        : formatDayAndTime(end, now, timeZone.value);
}
</script>

<style scoped>
@reference "@styles/tailwind.css";

.message {
    --message-color: inherit;
    --message-tint: transparent;

    display: grid;
    grid-template:
        "icon text text" auto
        "icon validity validity" auto
        "icon stops lines" auto
        / min-content minmax(0, 1fr) max-content;
    padding: --spacing(4);
    border-radius: var(--radius-lg);
    background-color: transparent;
    color: var(--message-color);
    transition: background-color var(--duration-normal) var(--ease-in-out);

    &.info {
        --message-color: var(--color-message-info);
        --message-tint: color-mix(in srgb, var(--color-info) 8%, transparent);
    }

    &.breakdown {
        --message-color: var(--color-message-breakdown);
        --message-tint: color-mix(in srgb, var(--color-danger) 8%, transparent);
    }

    &:hover {
        background-color: var(--message-tint);
    }

    @variant max-lg {
        grid-template:
            "icon text text" auto
            "icon validity validity" auto
            "icon stops stops" auto
            "icon lines lines" auto
            / min-content minmax(0, 1fr) max-content max-content;
    }

    & .text {
        grid-area: text;
        margin-block: calc(--spacing(1) * -1) --spacing(2);
    }

    & .icon-type {
        grid-area: icon;
        align-self: start;
        margin-inline-end: --spacing(3);
        font-size: var(--text-lg);
    }

    & .stops {
        grid-area: stops;
        align-self: center;
        margin-block-start: --spacing(1);
        font-size: var(--text-xs);
        font-weight: bold;
    }

    & .stop-list {
        @apply stop-list-inline;
    }

    & .validity {
        grid-area: validity;
        font-size: var(--text-xs);
    }

    & .lines {
        grid-area: lines;
        align-self: end;

        @variant max-lg {
            margin-block-start: --spacing(2);
        }

        & > ul {
            display: inline-block;
            margin: 0 0 calc(--spacing(1) * -1);
            padding: 0;

            & > li {
                display: inline-block;
                margin-block-end: --spacing(1);
                margin-inline-end: --spacing(1);
            }
        }
    }
}
</style>

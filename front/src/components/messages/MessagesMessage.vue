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

<script lang="ts">
import { Line, Stop } from "@/model";
import moment, { Moment } from "moment";
import { PropType } from "vue";
import { MessageType } from "@/model/message";

export default {
    name: "MessagesMessage",
    props: {
        message: {
            type: String,
            required: true,
        },
        lines: {
            type: Array as PropType<Line[]>,
            required: false,
            default: () => [],
        },
        stops: {
            type: Array as PropType<Stop[]>,
            required: false,
            default: () => [],
        },
        type: {
            type: String as PropType<MessageType>,
            required: false,
            default: () => "unknown",
        },
        validFrom: {
            type: Object as PropType<Moment>,
            required: false,
            default: null,
        },
        validTo: {
            type: Object as PropType<Moment>,
            required: false,
            default: null,
        },
        tag: {
            type: String,
            required: false,
            default: "div",
        },
    },
    setup() {
        return {
            today: moment(),
            moment,
        };
    },
};
</script>

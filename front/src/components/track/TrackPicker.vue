<template>
    <ui-select v-slot="{ option: track }" :options="tracks" allow-clearing>
        <div class="track">
            <line-symbol :line="track.line" class="symbol" />
            <span class="description">{{ track.description }}</span>
            <ul class="stops">
                <li v-for="stop in track.stops" :key="stop.id">
                    <stop-label :stop="stop" />
                </li>
            </ul>
        </div>
    </ui-select>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { Track } from "@/model";
import UiSelect from "@/components/ui/UiSelect.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";

defineProps({
    tracks: {
        type: Array as PropType<Track[]>,
        required: true,
    },
});
</script>

<style scoped>
@reference "@styles/tailwind.css";

/* the option's content: the line and description in a row, the stops under them */
.track {
    display: grid;
    grid-template-columns: min-content 1fr;
    grid-template-areas:
        "symbol description"
        "stops stops";

    & .symbol {
        grid-area: symbol;
    }

    & .description {
        grid-area: description;
        padding-inline-start: --spacing(2);
        font-weight: 600;
    }

    & .stops {
        grid-area: stops;
        overflow-x: hidden;
        margin-block-end: 0;
        padding-inline-start: 0;
        font-size: var(--text-xs);
        text-overflow: ellipsis;
        white-space: nowrap;

        & > li {
            display: inline-flex;

            &:not(:last-child)::after {
                margin-inline-end: --spacing(1);
                content: ", ";
            }
        }
    }
}
</style>

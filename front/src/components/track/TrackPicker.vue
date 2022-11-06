<template>
    <ui-select
        v-slot="{ option: track }"
        class="track-picker"
        :options="tracks"
        allow-clearing
    >
        <line-symbol :line="track.line" class="track-picker__line" />
        <span class="track-picker__description">{{ track.description }}</span>
        <ul class="track-picker__stops">
            <li v-for="stop in track.stops" :key="stop.id">
                <stop-label :stop="stop" />
            </li>
        </ul>
    </ui-select>
</template>

<script lang="ts" setup>
import { PropType } from "vue";
import { Track } from "@/model";
import UiSelect from "@/components/ui/UiSelect.vue";

defineProps({
    tracks: {
        type: Array as PropType<Track[]>,
        required: true,
    },
})
</script>

<style lang="scss">
@import "@styles/variables";

.track-picker {
    .ui-select__option:not(.ui-select__option--empty) {
        display: grid;

        grid-template-columns: min-content 1fr;
        grid-template-areas:
            "l d"
            "s s"
        ;
    }

    &__description {
        grid-area: d;
        font-weight: 600;
        padding-inline-start: 0.5rem;
    }

    &__label {
        grid-area: l;
    }

    &__stops {
        padding-left: 0;
        grid-area: s;
        font-size: 0.8rem;
        white-space: nowrap;
        overflow-x: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0;

        > li {
            display: inline-flex;
            &:not(:last-child)::after {
                content: ", ";
                margin-right: 0.2rem;
            }
        }
    }
}
</style>

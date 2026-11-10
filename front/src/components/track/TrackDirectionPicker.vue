<template>
    <ui-select
        :options="ordered"
        :model-value="modelValue"
        class="picker"
        @update:model-value="track => track && emit('update:modelValue', track)"
    >
        <template #selected="{ option: track }">
            Kierunek: <strong class="destination">{{ trackDestination(track)?.name }}</strong>
        </template>
        <template #default="{ option: track }">
            <span class="track">
                <span class="label">{{ trackLabel(track, tracks) }}</span>
                <!-- the leading space keeps the hint a word apart from the label -->
                <span v-if="track.kind !== 'main'" class="side"> skrócony / inny przebieg</span>
            </span>
        </template>
    </ui-select>
</template>

<script lang="ts" setup>
import { computed } from "vue";
import type { Track } from "@/model";
import { orderTracks, trackDestination, trackLabel } from "@/timetable";
import UiSelect from "@/components/ui/UiSelect.vue";

// a line's direction as riders pick it: "Kierunek: Jelitkowo", the main tracks listed before the side ones
defineOptions({ name: "TrackDirectionPicker" });

const props = defineProps<{
    /** The line's tracks, with their stops */
    tracks: Track[];
    modelValue?: Track;
}>();

const emit = defineEmits<{ "update:modelValue": [track: Track] }>();

const ordered = computed(() => orderTracks(props.tracks));
</script>

<style scoped>
@reference "@styles/tailwind.css";

.picker {
    /* a hairline, like the other fields' frames */
    border: 1px solid var(--color-divider);
    border-radius: var(--radius-md);
}

.destination {
    font-weight: 600;
}

.track {
    display: flex;
    flex-direction: column;
}

/* a side track is a quiet aside, not a section of its own */
.side {
    color: var(--color-muted);
    font-size: var(--text-xs);
}
</style>

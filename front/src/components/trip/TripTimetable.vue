<template>
    <div class="trip">
        <ol ref="stops" v-dragscroll.x="!isTouch" class="trip__stops">
            <li
                v-for="stop in stops"
                :key="stop.order"
                class="trip__stop"
                :class="[stop.current && 'trip__stop--current', stop.visited && 'trip__stop--visited']"
            >
                <div class="trip__marker" />
                <div class="trip__description">
                    <stop-label :stop="stop.stop" />
                </div>
                <div class="trip__departure">
                    {{ stop.departure.format("HH:mm") }}
                </div>
            </li>
        </ol>
    </div>
</template>

<script lang="ts" setup>
import { computed, onMounted, PropType, useTemplateRef } from "vue";
import { TimetableEntry } from "@/model/trip";
import { Stop } from "@/model";
import moment from "moment";
import StopLabel from "@/components/stop/StopLabel.vue";
import { useIsTouch } from "@/composables/useIsTouch";

const isTouch = useIsTouch();

type TimetableEntryInfo = TimetableEntry & { visited: boolean; current: boolean };

defineOptions({ name: "TripTimetable" });

const props = defineProps({
    timetable: {
        type: Array as PropType<TimetableEntry[]>,
        required: true,
    },
    current: {
        type: Object as PropType<Stop>,
        required: true,
    },
});

const list = useTemplateRef<HTMLOListElement>("stops");

const stops = computed<TimetableEntryInfo[]>(() =>
    props.timetable.map(stop => ({
        ...stop,
        current: stop.stop.id == props.current.id,
        visited: moment().isAfter(stop.departure),
    })),
);

const scrollToCurrent = () => {
    const current = list.value?.querySelector<HTMLLIElement>(".trip__stop--current");

    if (!list.value || !current) return;

    list.value.scrollLeft = current.offsetLeft - (list.value.clientWidth + current.clientWidth) / 2;
};

onMounted(scrollToCurrent);
</script>

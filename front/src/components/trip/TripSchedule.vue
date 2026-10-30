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
import { ScheduledStop } from "@/model/trip";
import { Stop } from "@/model";
import moment from "moment";
import StopLabel from "@/components/stop/StopLabel.vue";
import { useIsTouch } from "@/composables/useIsTouch";

const isTouch = useIsTouch();

type ScheduledStopInfo = ScheduledStop & { visited: boolean; current: boolean };

defineOptions({ name: "TripSchedule" });

const props = defineProps({
    schedule: {
        type: Array as PropType<ScheduledStop[]>,
        required: true,
    },
    current: {
        type: Object as PropType<Stop>,
        required: true,
    },
});

const list = useTemplateRef<HTMLOListElement>("stops");

const stops = computed<ScheduledStopInfo[]>(() =>
    props.schedule.map(stop => ({
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

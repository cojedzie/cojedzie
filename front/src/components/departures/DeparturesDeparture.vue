<template>
    <div class="departure">
        <div class="departure__line">
            <line-symbol :line="departure.line" />
            <div class="line__display">
                {{ departure.display }}
            </div>
        </div>

        <div class="departure__time">
            <template v-if="!departure.estimated">
                <ui-tooltip placement="top-end">
                    Czas rozkładowy, nieuwzględniający aktualnej sytuacji komunikacyjnej.
                </ui-tooltip>
                <ui-icon icon="departure-warning" class="mr-2" />
            </template>

            <template v-if="!showRelativeTime">
                <span v-if="timeDiffers" :class="['departure__time', 'departure__time--delayed']">
                    {{ departure.scheduled.format("HH:mm") }}
                </span>
                {{ " " }}
                <span
                    v-if="departure.delay < 0 || departure.delay > 30"
                    class="badge"
                    :class="[departure.delay < 0 ? 'badge-danger' : 'badge-warning']"
                >
                    {{ signed(departure.delay) }}s
                </span>
                {{ " " }}
                <span class="departure__time">{{ time.format("HH:mm") }}</span>
            </template>
            <template v-else>
                {{ timeLeft.humanize(true) }}
            </template>
        </div>

        <div class="departure__stop">
            <ui-icon icon="stop" fixed-width class="mr-1 shrink-0" />
            <stop-label :stop="departure.stop" />

            <div class="stop__actions flex-space-left">
                <button class="btn btn-action" @click="showTrip = !showTrip">
                    <ui-tooltip>pokaż/ukryj trasę</ui-tooltip>
                    <ui-icon icon="track" />
                </button>
            </div>
        </div>
    </div>
    <ui-fold :visible="showTrip">
        <trip-schedule
            v-if="trip"
            :schedule="trip.schedule"
            :current="departure.stop"
            :class="[`trip--${departure.line.type}`]"
        />
        <div v-else class="text-center">
            <ui-icon icon="spinner" />
        </div>
    </ui-fold>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import moment from "moment";
import type { Departure } from "@/model";
import type { Trip } from "@/model/trip";
import api from "@/api";
import { type Jsonified, signed } from "@/utils";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiFold from "@/components/ui/UiFold.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";
import TripSchedule from "@/components/trip/TripSchedule.vue";

defineOptions({ name: "DeparturesDeparture" });

const props = defineProps<{ departure: Departure }>();

function convertTripDtoToTrip(trip: Jsonified<Trip>): Trip {
    return {
        ...trip,
        schedule: trip.schedule.map(scheduled => ({
            ...scheduled,
            arrival: moment.parseZone(scheduled.arrival).local(),
            departure: moment.parseZone(scheduled.departure).local(),
        })),
    };
}

const { relativeTimes, relativeTimesLimit, relativeTimesLimitEnabled, relativeTimesForScheduled } =
    storeToRefs(useDeparturesSettingsStore());

const showTrip = ref<boolean>(false);
const scheduledTrip = ref<Trip>(null);

const time = computed(() => props.departure.estimated || props.departure.scheduled);
const timeLeft = computed(() => moment.duration(time.value.diff(moment())));
const timeDiffers = computed(() => {
    const { departure } = props;
    return departure.estimated && departure.scheduled.format("HH:mm") !== departure.estimated.format("HH:mm");
});

const trip = computed(() => {
    const trip = scheduledTrip.value;

    return (
        trip && {
            ...trip,
            schedule: trip.schedule.map(stop => ({
                ...stop,
                arrival: stop.arrival.clone().add(props.departure.delay, "seconds"),
                departure: stop.departure.clone().add(props.departure.delay, "seconds"),
            })),
        }
    );
});

const showRelativeTime = computed(() => {
    if (!relativeTimes.value) {
        return false;
    }

    if (!props.departure.estimated && !relativeTimesForScheduled.value) {
        return false;
    }

    return !(relativeTimesLimitEnabled.value && time.value.diff(moment(), "minutes") > relativeTimesLimit.value);
});

async function downloadTrips() {
    if (showTrip.value != true || trip.value != null) {
        return;
    }

    const response = await api.get("v1_trip_details", {
        params: { id: props.departure.trip?.id },
        version: "^1.0",
    });

    if (response.status === 200) {
        scheduledTrip.value = convertTripDtoToTrip(response.data);
    }
}

watch(showTrip, downloadTrips);
</script>

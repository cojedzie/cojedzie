<template>
    <div class="departure">
        <div class="route">
            <line-symbol :line="departure.line" class="symbol" />
            <div class="headsign">
                {{ departure.display }}
            </div>
        </div>

        <div class="time">
            <template v-if="!departure.estimated">
                <ui-tooltip placement="top-end">
                    Czas rozkładowy, nieuwzględniający aktualnej sytuacji komunikacyjnej.
                </ui-tooltip>
                <ui-icon icon="departure-warning" class="warning" />
            </template>

            <template v-if="!showRelativeTime">
                <span v-if="timeDiffers" class="scheduled">
                    {{ clock(departure.scheduled) }}
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
                <span>{{ clock(time) }}</span>
            </template>
            <template v-else>
                {{ timeLeft }}
            </template>
        </div>

        <div class="place">
            <ui-icon icon="stop" fixed-width class="place-icon" />
            <stop-label :stop="departure.stop" />

            <div class="tools">
                <button class="btn btn-action" @click="showTrip = !showTrip">
                    <ui-tooltip>pokaż/ukryj trasę</ui-tooltip>
                    <ui-icon icon="track" />
                </button>
            </div>
        </div>
    </div>
    <ui-fold :visible="showTrip">
        <trip-timetable v-if="trip" :timetable="trip.timetable" :current="departure.stop" :type="departure.line.type" />
        <div v-else class="loading">
            <ui-icon icon="spinner" />
        </div>
    </ui-fold>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { storeToRefs } from "pinia";
import type { Departure } from "@/model";
import type { Trip } from "@/model/trip";
import api from "@/api";
import { type Jsonified, signed } from "@/utils";
import { useDeparturesSettingsStore } from "@/stores/departures-settings";
import { useDisplayTimeZone } from "@/composables/useDisplayTimeZone";
import { formatClockTime, formatRelativeTime, Temporal, wholeMinutesBetween } from "@/time";
import UiIcon from "@/components/ui/UiIcon.vue";
import UiFold from "@/components/ui/UiFold.vue";
import UiTooltip from "@/components/ui/UiTooltip.vue";
import StopLabel from "@/components/stop/StopLabel.vue";
import LineSymbol from "@/components/line/LineSymbol.vue";
import TripTimetable from "@/components/trip/TripTimetable.vue";

defineOptions({ name: "DeparturesDeparture" });

const props = defineProps<{ departure: Departure }>();

function convertTripDtoToTrip(trip: Jsonified<Trip>): Trip {
    return {
        ...trip,
        timetable: trip.timetable.map(scheduled => ({
            ...scheduled,
            arrival: Temporal.Instant.from(scheduled.arrival),
            departure: Temporal.Instant.from(scheduled.departure),
        })),
    };
}

const { relativeTimes, relativeTimesLimit, relativeTimesLimitEnabled, relativeTimesForScheduled } =
    storeToRefs(useDeparturesSettingsStore());

const showTrip = ref<boolean>(false);
const scheduledTrip = ref<Trip>(null);

const time = computed(() => props.departure.estimated || props.departure.scheduled);
const timeZone = useDisplayTimeZone();
const clock = (instant: Temporal.Instant) => formatClockTime(instant, timeZone.value);

const timeLeft = computed(() => formatRelativeTime(time.value, Temporal.Now.instant()));
const timeDiffers = computed(() => {
    const { departure } = props;
    return departure.estimated && clock(departure.scheduled) !== clock(departure.estimated);
});

const trip = computed(() => {
    const trip = scheduledTrip.value;

    return (
        trip && {
            ...trip,
            timetable: trip.timetable.map(stop => ({
                ...stop,
                arrival: stop.arrival.add({ seconds: props.departure.delay }),
                departure: stop.departure.add({ seconds: props.departure.delay }),
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

    return !(
        relativeTimesLimitEnabled.value &&
        wholeMinutesBetween(Temporal.Now.instant(), time.value) > relativeTimesLimit.value
    );
});

async function downloadTrips() {
    if (showTrip.value != true || trip.value != null) {
        return;
    }

    const response = await api.get("v1_trip_details", {
        params: { id: props.departure.trip?.id },
        version: "^1.1",
    });

    if (response.status === 200) {
        scheduledTrip.value = convertTripDtoToTrip(response.data);
    }
}

watch(showTrip, downloadTrips);
</script>

<style scoped>
@reference "@styles/tailwind.css";

.departure {
    display: flex;
    flex-wrap: wrap;
    align-items: center;

    & .route {
        display: flex;
        flex: 3 0;
        align-items: center;
    }

    & .symbol {
        min-inline-size: --spacing(24);
    }

    & .headsign {
        line-height: 1.2;
    }

    & .time {
        /* room for a delayed time, its delay and the estimate */
        inline-size: 9rem;
        text-align: end;
    }

    & .warning {
        margin-inline-end: --spacing(2);
    }

    & .scheduled {
        text-decoration: line-through;
    }

    & .place {
        display: flex;
        align-items: center;
        inline-size: 100%;
    }

    & .place-icon {
        flex-shrink: 0;
        margin-inline-end: --spacing(1);
    }

    & .tools {
        display: flex;
        margin-inline-start: auto;
    }

    /* the list's breakpoints */
    @container departures (width >= 576px) {
        & .time {
            margin-inline-start: auto;
        }
    }

    @container departures (width >= 768px) {
        & .time {
            order: 2;
        }

        & .place {
            flex: 2 0;
            inline-size: auto;
        }
    }
}

.loading {
    text-align: center;
}
</style>

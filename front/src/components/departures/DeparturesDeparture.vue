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
                <span v-if="timeDiffers" :class="[ 'departure__time', 'departure__time--delayed']">
                    {{ departure.scheduled.format('HH:mm') }}
                </span>
                {{ ' ' }}
                <span
                    v-if="departure.delay < 0 || departure.delay > 30"
                    class="badge"
                    :class="[departure.delay < 0 ? 'badge-danger' : 'badge-warning']"
                >
                    {{ $f.signed(departure.delay) }}s
                </span>
                {{ ' ' }}
                <span class="departure__time">{{ time.format('HH:mm') }}</span>
            </template>
            <template v-else>
                {{ $f.duration(timeLeft).humanize(true) }}
            </template>
        </div>

        <div class="departure__stop">
            <ui-icon icon="stop" fixed-width class="mr-1 flex-shrink-0" />
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
        <trip-schedule v-if="trip" :schedule="trip.schedule" :current="departure.stop" :class="[ `trip--${departure.line.type}` ]" />
        <div v-else class="text-center">
            <ui-icon icon="spinner" />
        </div>
    </ui-fold>
</template>

<script lang="ts">
import { computed, PropType, ref, unref, watch } from "vue";
import { Departure } from "@/model";
import moment from "moment";
import { Trip } from "@/model/trip";
import api from "@/api";
import { Jsonified } from "@/utils";
import { useStore, VuexStore } from "vuex";
import { StoreDefinition } from "@/store/initializer";
import { DeparturesSettingsState } from "@/store/modules/settings/departures";

function convertTripDtoToTrip(trip: Jsonified<Trip>): Trip {
    return {
        ...trip,
        schedule: trip.schedule.map(scheduled => ({
            ...scheduled,
            arrival: moment.parseZone(scheduled.arrival),
            departure: moment.parseZone(scheduled.departure),
        }))
    };
}

function useDepartureSetting(store: VuexStore<StoreDefinition>, setting: keyof DeparturesSettingsState) {
    return computed(() => store.state['departures-settings'][setting]);
}

export default {
    name: "DeparturesDeparture",
    props: {
        departure: {
            type: Object as PropType<Departure>,
            required: true
        }
    },
    setup(props) {
        const store = useStore<StoreDefinition>();

        const showTrip = ref<boolean>(false);
        const scheduledTrip = ref<Trip>(null);

        const time = computed(() => props.departure.estimated || props.departure.scheduled);
        const timeLeft = computed(() => moment.duration(time.value.diff(moment())));
        const timeDiffers = computed(() => {
            const { departure } = props;
            return departure.estimated && departure.scheduled.format('HH:mm') !== departure.estimated.format('HH:mm');
        })

        const trip = computed(() => {
            const trip = scheduledTrip.value;

            return trip && {
                ...trip,
                schedule: trip.schedule.map(stop => ({
                    ...stop,
                    arrival: stop.arrival.clone().add(props.departure.delay, 'seconds'),
                    departure: stop.departure.clone().add(props.departure.delay, 'seconds'),
                }))
            };
        })

        const relativeTimes = useDepartureSetting(store, 'relativeTimes');
        const relativeTimesLimit = useDepartureSetting(store, 'relativeTimesLimit');
        const relativeTimesLimitEnabled = useDepartureSetting(store, 'relativeTimesLimitEnabled');
        const relativeTimesForScheduled = useDepartureSetting(store, 'relativeTimesForScheduled');

        const showRelativeTime = computed(() => {
            if (!unref(relativeTimes)) {
                return false;
            }

            const departure = props.departure;
            if (!departure.estimated && !unref(relativeTimesForScheduled)) {
                return false;
            }

            const now = moment();

            return !(unref(relativeTimesLimitEnabled) && time.value.diff(now, "minutes") > unref(relativeTimesLimit));
        });

        async function downloadTrips() {
            if (showTrip.value != true || trip.value != null) {
                return;
            }

            const response = await api.get('v1_trip_details', {
                params: { id: props.departure.trip?.id },
                version: "^1.0"
            })

            if (response.status === 200) {
                scheduledTrip.value = convertTripDtoToTrip(response.data);
            }
        }

        watch(showTrip, downloadTrips)

        return {
            showTrip,
            showRelativeTime,
            time,
            timeLeft,
            timeDiffers,
            trip,
            relativeTimesLimit,
            relativeTimesLimitEnabled,
            relativeTimes,
            relativeTimesForScheduled
        }
    }
}
</script>

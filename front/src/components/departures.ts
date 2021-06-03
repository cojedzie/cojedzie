/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

import Vue from 'vue'
import { Departure } from "@/model";
import { Component, Prop, Watch } from "vue-property-decorator";
import store, { Departures, DeparturesSettings } from '../store'
import { Trip } from "@/model/trip";
import { Jsonified } from "@/utils";
import * as moment from "moment";
import api from "@/api";

@Component({ template: require("@templates/departures.html"), store })
export class DeparturesComponent extends Vue {
    @Departures.State departures: Departure[];
}

@Component({ template: require("@templates/departures/departure.html"), store })
export class DepartureComponent extends Vue {
    @Prop(Object) departure: Departure;
    scheduledTrip: Trip = null;

    @DeparturesSettings.State
    relativeTimes: boolean;

    showTrip: boolean = false;

    processTrip(trip: Jsonified<Trip>): Trip {
        return {
            ...trip,
            schedule: trip.schedule.map(s => ({
                ...s,
                arrival: moment.parseZone(s.arrival),
                departure: moment.parseZone(s.departure),
            }))
        };
    };

    get timeDiffers() {
        const departure = this.departure;

        return departure.estimated && departure.scheduled.format('HH:mm') !== departure.estimated.format('HH:mm');
    }

    get time() {
        return this.departure.estimated || this.departure.scheduled;
    }

    get timeLeft() {
        return moment.duration(this.time.diff(moment()));
    }

    @Watch('showTrip')
    async downloadTrips() {
        if (this.showTrip != true || this.trip != null) {
            return;
        }

        const response = await api.get('v1_trip_details', {
            params: { id: this.departure.trip?.id },
            version: "^1.0"
        })

        if (response.status === 200) {
            this.scheduledTrip = this.processTrip(response.data);
        }
    }

    get trip() {
        const trip = this.scheduledTrip;
        return trip && {
            ...trip,
            schedule: trip.schedule.map(stop => ({
                ...stop,
                arrival: stop.arrival.clone().add(this.departure.delay, 'seconds'),
                departure: stop.departure.clone().add(this.departure.delay, 'seconds'),
            }))
        };
    }
}

Vue.component('Departures', DeparturesComponent);
Vue.component('Departure', DepartureComponent);

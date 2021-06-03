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
import { Component } from 'vue-property-decorator'
import { Provider } from "@/model";
import * as moment from 'moment';
import api from "@/api";

@Component({
    template: require('@templates/page/providers.html'),
})
export class ProviderChooser extends Vue {
    providers: Provider[] = [];

    mounted() {
        document.querySelector<HTMLLinkElement>('link[rel="manifest"]').href = "/manifest.json";
    }

    async created() {
        const response = await api.get('v1_provider_list', { version: "^1.0" })

        this.providers = response.data.map<Provider>(provider => {
            return {
                ...provider,
                lastUpdate: provider.lastUpdate && moment(provider.lastUpdate)
            }
        });
    }
}

Vue.component('ProviderChooser', ProviderChooser);

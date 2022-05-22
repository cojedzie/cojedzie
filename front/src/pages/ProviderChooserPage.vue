<template>
    <div style="width: 100%">
        <ui-map :center="{ lat: 52.0194, lon: 19.1451 }" :zoom="7" :options="{ zoomControl: false }" class="map">
            <div class="provider-picker">
                <h2 class="provider-picker__heading">
                    Wybierz lokalizację
                </h2>
                <ul class="provider-picker__providers">
                    <li v-for="provider in providers" :key="provider.id" class="provider-picker__provider">
                        <a :href="`/${provider.id}`" class="provider">
                            <ui-icon icon="line-bus" size="2x" />
                            <div>
                                <div class="provider__short-name">{{ provider.shortName }}</div>
                                <div class="provider__name">{{ provider.name }}</div>
                            </div>
                            <ui-tooltip v-if="provider.lastUpdate != null">Ostatnia akutalizacja: {{ provider.lastUpdate.format('YYYY-MM-DD HH:mm') }}</ui-tooltip>
                        </a>
                    </li>
                </ul>
            </div>

            <l-marker v-for="provider in providers" :key="provider.id" :lat-lng="provider.location" :options="{ keyboard: false }">
                <l-icon>
                    <div class="map__label-box" tabindex="0">
                        <a :href="`/${provider.id}`" class="provider">
                            <ui-icon icon="line-bus" class="map__icon" />
                            <div>
                                <div class="provider__short-name">{{ provider.shortName }}</div>
                                <div class="provider__name">{{ provider.name }}</div>
                            </div>
                        </a>
                    </div>
                </l-icon>
            </l-marker>
        </ui-map>
    </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component'
import { Provider } from "@/model";
import moment from 'moment';
import api from "@/api";

@Options({ name: "ProviderChooserPage" })
export default class ProviderChooserPage extends Vue {
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
</script>

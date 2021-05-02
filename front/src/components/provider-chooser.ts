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
        const response = await api.get('v1_provider_list', { version: "1.0" })

        this.providers = response.data.map<Provider>(provider => {
            return {
                ...provider,
                lastUpdate: provider.lastUpdate && moment(provider.lastUpdate)
            }
        });
    }
}

Vue.component('ProviderChooser', ProviderChooser);

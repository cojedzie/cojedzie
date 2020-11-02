import Vue from 'vue'
import { Component } from 'vue-property-decorator'
import { Provider } from "@/model";
import { Jsonified } from "@/utils";
import * as moment from 'moment';

@Component({
    template: require('@templates/page/providers.html'),
})
export class ProviderChooser extends Vue {
    private providers: Provider[] = [];

    async created() {
        const response = await fetch('/api/v1/providers');
        const result = await response.json() as Jsonified<Provider>[];

        this.providers = result.map<Provider>(provider => {
            return {
                ...provider,
                lastUpdate: provider.lastUpdate && moment(provider.lastUpdate)
            }
        });
    }
}

Vue.component('ProviderChooser', ProviderChooser);

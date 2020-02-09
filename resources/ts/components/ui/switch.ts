import Vue from 'vue'
import { Component, Prop } from 'vue-property-decorator'
import * as uuid from "uuid";

@Component({
    template: require('../../../components/ui/switch.html'),
    inheritAttrs: false
})
export class UiSwitch extends Vue {
    @Prop({
        type: String,
        default: () => `uuid-${uuid.v4()}`
    })
    id: string;

    @Prop(Boolean)
    value: boolean;

    update(ev) {
        this.$emit('input', !this.value);
    }
}

Vue.component('UiSwitch', UiSwitch);

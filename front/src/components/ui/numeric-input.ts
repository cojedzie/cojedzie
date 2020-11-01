import Vue from 'vue'
import { Component, Prop } from 'vue-property-decorator'
import * as uuid from "uuid";

@Component({
    template: require('../../../templates/ui/numeric.html'),
    inheritAttrs: false
})
export class UiNumericInput extends Vue {
    @Prop({
        type: String,
        default: () => `uuid-${uuid.v4()}`
    })
    id: string;

    @Prop(Number)
    value: number;

    @Prop({ type: Number, default: 1 })
    step: number;

    @Prop({ type: Number, default: -Infinity })
    min: number;

    @Prop({ type: Number, default: Infinity })
    max: number;

    update(ev) {
        this.$emit('input', this.clamp(Number.parseInt(ev.target.value)));
    }

    increment() {
        this.$emit('input', this.clamp(this.value + this.step));
    }

    decrement() {
        this.$emit('input', this.clamp(this.value - this.step));
    }

    clamp(value: number) {
        return Math.max(Math.min(value, this.max), this.min);
    }

    get canIncrement(): boolean {
        return this.max - this.value > Number.EPSILON * 2;
    }

    get canDecrement(): boolean {
        return this.value - this.min > Number.EPSILON * 2;
    }
}

Vue.component('UiNumericInput', UiNumericInput);

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

import { Prop } from 'vue-property-decorator'
import { Options, Vue } from "vue-class-component";
import * as uuid from "uuid";
import WithRender from "@templates/ui/numeric.html";

@WithRender
@Options({
    name: "UiNumericInput",
    inheritAttrs: false,
    emits: ['update:value'],
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
        this.$emit('update:value', this.clamp(Number.parseInt(ev.target.value)));
    }

    increment() {
        this.$emit('update:value', this.clamp(this.value + this.step));
    }

    decrement() {
        this.$emit('update:value', this.clamp(this.value - this.step));
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

export default UiNumericInput;

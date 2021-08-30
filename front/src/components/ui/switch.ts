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
import * as uuid from "uuid";
import { Options, Vue } from "vue-class-component";
import { app } from "@/components";

@Options({
    render: require('@templates/ui/switch.html').render,
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

app.component('UiSwitch', UiSwitch);

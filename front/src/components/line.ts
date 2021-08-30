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
import { Line } from "@/model";
import { Options, Vue } from "vue-class-component";
import { app } from "@/components/application";

@Options({ template: require('@templates/line.html' )})
export class LineComponent extends Vue {
    @Prop(Object)
    public line: Line;

    @Prop(Boolean)
    public simple: boolean;
}

app.component('LineSymbol', LineComponent);

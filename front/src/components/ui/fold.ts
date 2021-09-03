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

import { Options, Vue } from "vue-class-component";
import { Prop, Watch } from "vue-property-decorator";
import WithRender from "@templates/ui/fold.html"

@WithRender
@Options({ name: "UiFold" })
export class UiFold extends Vue {
    private observer: MutationObserver;

    @Prop(Boolean)
    public visible: boolean;

    @Prop(Boolean)
    public lazy: boolean;

    mounted() {
        this.resize();

        this.observer = new MutationObserver(() => this.resize());
        this.observer.observe(this.$refs['inner'] as Node, {
            characterData: true,
            subtree: true,
            childList: true
        });
    }

    beforeDestroy() {
        this.observer.disconnect();
    }

    @Watch('visible')
    private resize() {
        const inner = this.$refs['inner'] as HTMLDivElement;
        (this.$el as HTMLElement).style.height = `${ (this.visible && inner) ? inner.clientHeight : 0 }px`;
    }
}

export default UiFold;

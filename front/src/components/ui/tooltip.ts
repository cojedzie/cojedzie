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
import WithRender from "@templates/ui/tooltip.html";

type Events = {
    [event: string]: (...any) => void,
}
type Trigger = "hover" | "focus" | "long-press";
const longPressTimeout = 1000;
export const openedTooltips: Set<UiTooltip> = new Set<UiTooltip>();

@WithRender
@Options({ name: "UiTooltip" })
export class UiTooltip extends Vue {
    @Prop({ type: String, default: "top" }) public placement: string;
    @Prop({ type: Number, default: 400 }) public delay: number;
    @Prop({ type: Array, default: () => ["hover", "focus", "long-press"] }) public triggers: Trigger[];

    public show: boolean = false;
    public root: HTMLElement = null;

    private _events: Events = {};

    @Watch('show')
    updateOpenTooltipsList(show) {
        if (show) {
            openedTooltips.add(this);
        } else {
            openedTooltips.delete(this);
        }
    }

    mounted() {
        this.root = (this.$refs['root'] as HTMLSpanElement).parentElement;
        this.updateTriggers();
    }

    beforeDestroy() {
        openedTooltips.delete(this);
        this._removeEventListeners();
    }

    @Watch('triggers', { deep: true })
    updateTriggers() {
        this._removeEventListeners();

        this._events = {};

        let blocked: boolean = false;

        if (this.triggers.includes("hover") && !this.$isTouch) {
            let timeout;

            this._events['mouseenter'] = () => {
                timeout = setTimeout(() => {
                    this.show = !blocked
                }, this.delay);
            };

            this._events['mouseleave'] = () => {
                clearTimeout(timeout);
                this.show = false
            };
        }

        if (this.triggers.includes("focus") || (this.triggers.includes("hover") && this.$isTouch)) {
            if (this.$isTouch) {
                this._events['touchstart'] = () => {
                    // this is to prevent showing tooltips after tap
                    blocked = true;
                    setTimeout(() => blocked = false, longPressTimeout - 50);
                }
            }

            this._events['focus'] = () => {
                this.show = !blocked;
            };

            this._events['blur'] = () => {
                this.show = false
            };
        }

        if (this.triggers.includes("long-press") && this.$isTouch) {
            let timeout;

            this._events['touchstart'] = () => {
                timeout = setTimeout(() => {
                    this.show = true
                }, longPressTimeout);

                // this is to prevent showing tooltips after tap
                blocked = true;
                setTimeout(() => blocked = false, longPressTimeout - 50);
            };

            this._events['touchend'] = ev => {
                clearTimeout(timeout);

                if (this.show) {
                    ev.preventDefault();
                    this.root.focus();
                }
            };

            this._events['blur'] = () => {
                this.show = false
            };
        }

        this._registerEventListeners();
    }

    private _registerEventListeners() {
        for (const [event, handler] of Object.entries(this._events)) {
            this.root.addEventListener(event, handler);
        }
    }

    private _removeEventListeners() {
        for (const [event, handler] of Object.entries(this._events)) {
            this.root.removeEventListener(event, handler);
        }
    }
}

document.addEventListener('touchstart', () => {
    for (let tooltip of openedTooltips.values()) {
        tooltip.show = false;
    }
})

export default UiTooltip;

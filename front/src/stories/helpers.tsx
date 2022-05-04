/*
 * Copyright (C) 2022 Kacper Donat
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

import { useRef, useEffect, createElement } from "react";
import { Component, createApp } from "vue";

export type VProps<TComponent extends Component> = {
    component: TComponent
} & (TComponent extends Component<infer TProps> ? TProps : unknown);

export function V<TComponent extends Component>({ component, ...args }: VProps<TComponent>) {
    const el = useRef();

    useEffect(() => {
        const app = createApp(component, args);
        app.mount(el.current);

        return () => app.unmount();
    }, [ el ])

    return createElement(
        "div",
        null,
        createElement("div", { ref: el })
    );
}

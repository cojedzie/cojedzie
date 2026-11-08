/*
 * Copyright (C) 2026 Kacper Donat
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

import { useAppConfig } from "@/composables/useAppConfig";
import { PredefinedIcon } from "@/icons";

export interface AboutLink {
    label: string;
    href: string;
    icon?: PredefinedIcon;
}

export function useAboutLinks(): { version: string; year: number; links: AboutLink[] } {
    const config = useAppConfig();

    const links: AboutLink[] = [
        { label: "API", href: "/api/", icon: "api" },
        ...(config.production
            ? []
            : [{ label: "Storybook", href: "/storybook/", icon: "storybook" } satisfies AboutLink]),
        { label: "GitHub", href: "https://github.com/cojedzie/cojedzie", icon: "github" },
    ];

    return { version: config.version, year: new Date().getFullYear(), links };
}

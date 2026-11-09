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

import { computed, inject, type InjectionKey, provide, type Ref } from "vue";
import { Temporal } from "@/time";

const DisplayTimeZoneKey: InjectionKey<Readonly<Ref<string>>> = Symbol("display time zone");

/**
 * Makes `timeZone` the zone components below show clock times in, see `useDisplayTimeZone`. Days are not affected:
 * they are always the provider's (`useToday`).
 */
export function provideDisplayTimeZone(timeZone: Readonly<Ref<string>>): void {
    provide(DisplayTimeZoneKey, timeZone);
}

/**
 * The time zone clock times are shown in: the provider's, as the application provides it (a rider's preference may
 * provide another later). Without a provided one (stories, tests) it is the device's.
 */
export function useDisplayTimeZone(): Readonly<Ref<string>> {
    return inject(DisplayTimeZoneKey, () => computed(() => Temporal.Now.timeZoneId()), true);
}

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

import {
    inject,
    type InjectionKey,
    type MaybeRefOrGetter,
    provide,
    type Ref,
    shallowRef,
    toValue,
    watchEffect,
} from "vue";
import { useIntervalFn, useNow } from "@vueuse/core";
import { Temporal, todayIn } from "@/time";

const TodayKey: InjectionKey<Readonly<Ref<Temporal.PlainDate>>> = Symbol("today");

/**
 * Today's date in the given time zone, or the device's when there is none, kept current across midnight. It changes
 * only when the day does.
 */
export function useTodayIn(timeZone: MaybeRefOrGetter<string | undefined>): Readonly<Ref<Temporal.PlainDate>> {
    const now = useNow({ scheduler: callback => useIntervalFn(callback, 60_000) });
    const today = shallowRef(todayIn(Temporal.Now.timeZoneId()));

    watchEffect(() => {
        const current = todayIn(
            toValue(timeZone) ?? Temporal.Now.timeZoneId(),
            Temporal.Instant.fromEpochMilliseconds(now.value.getTime()),
        );

        if (!current.equals(today.value)) {
            today.value = current;
        }
    });

    return today;
}

/** Makes `today` the day components below take as today, see `useToday`. */
export function provideToday(today: Readonly<Ref<Temporal.PlainDate>>): void {
    provide(TodayKey, today);
}

/**
 * Today's date as the app sees it: in the provider's time zone, as the application provides it. Without a provided
 * one (stories, tests) it is today in the device's time zone.
 */
export function useToday(): Readonly<Ref<Temporal.PlainDate>> {
    return inject(TodayKey, () => useTodayIn(undefined), true);
}

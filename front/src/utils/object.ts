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

import { Optionalify } from "@/utils/index";

export function map<T extends object, KT extends keyof T, R extends { [KR in keyof T] }>(source: T, mapper: (value: T[KT], key: KT) => R[KT]): R {
    const result: R = {} as R;

    for (const [key, value] of Object.entries(source)) {
        result[key] = mapper(value as T[KT], key as KT);
    }

    return result;
}

export function filter<T, KT extends keyof T>(source: T, filter: (value: T[KT], key: KT) => boolean): Optionalify<T> {
    const result: Optionalify<T> = {};

    for (const [key, value] of Object.entries(source)) {
        if (filter(value as T[KT], key as KT)) {
            result[key] = value;
        }
    }

    return result;
}

export function except<T>(source: T, keys: (keyof T)[]) {
    return filter(source, (_, key) => !keys.includes(key))
}

export function only<T>(source: T, keys: (keyof T)[]) {
    return filter(source, (_, key) => keys.includes(key))
}

export function merge<T1 extends object, T2 extends object, TReturn extends { [K in (keyof T1 & keyof T2)]: T1[K] | T2[K] }>(
    first: T1,
    second: T2,
    resolve: <TKey extends (keyof T1 & keyof T2)>(a: T1[TKey], b: T2[TKey], key: TKey) => TReturn[TKey] = <TKey extends (keyof T1 & keyof T2)>(a, _) => (a as TReturn[TKey])
) {
    const result = { ...first, ...second }

    const keysOfFirst = Object.keys(first);
    const keysOfSecond = Object.keys(second);

    const keys = keysOfFirst.length < keysOfSecond.length ? keysOfFirst : keysOfSecond;

    for (const key of keys) {
        result[key] = resolve(first[key], second[key], key as keyof T1 & keyof T2);
    }

    return result;
}

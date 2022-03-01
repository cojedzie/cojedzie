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

import { createDecorator, Vue } from 'vue-class-component'

export interface Decorator<TArgs extends unknown[], FArgs extends unknown[], TRet, FRet> {
    decorate(f: (...farg: FArgs) => unknown, ...args: TArgs): (...farg: FArgs) => TRet;

    (...args: TArgs): (target, name: string | symbol, descriptor: TypedPropertyDescriptor<(...farg: FArgs) => FRet>) => void;
}

export function decorator<TArgs extends unknown[], FArgs extends unknown[], TRet, FRet>
    (decorate: (f: (...farg: FArgs) => FRet, ...args: TArgs) => (...farg: FArgs) => TRet)
    : Decorator<TArgs, FArgs, TRet, FRet> {

    const factory = function (this: Decorator<TArgs, FArgs, TRet, FRet>, ...args: TArgs) {
        return (target, name: string | symbol, descriptor: PropertyDescriptor) => {
            descriptor.value = decorate(descriptor.value, ...args);
        }
    } as Decorator<TArgs, FArgs, TRet, FRet>;
    factory.decorate = decorate;

    return factory;
}

export const throttle = decorator(function (decorated, time: number) {
    let timeout;
    return function (this: unknown, ...args) {
        if (typeof timeout === 'undefined') {
            timeout = setTimeout(() => {
                decorated.call(this, ...args);
                timeout = undefined;
            }, time);
        }
    }
});

export const debounce = decorator(function (decorated, time: number) {
    let timeout;
    return function (this: unknown, ...args) {
        if (typeof timeout !== 'undefined') {
            clearTimeout(timeout);
        }

        timeout = setTimeout(() => {
            timeout = undefined;
            decorated.call(this, ...args);
        }, time);
    }
});

export const condition = decorator(function <TArgs extends unknown[], TReturn>(decorated: (...args: TArgs) => TReturn, predicate: (...args: TArgs) => boolean) {
    return function (this: unknown, ...args: TArgs) {
        if (predicate(...args)) {
            return decorated(...args);
        }
    }
});

export const notify = (name?: string) => createDecorator((options, key) => {
    const symbol = Symbol(key);

    if (typeof options.computed === 'undefined') {
        options.computed = {};
    }

    options.computed[key] = {
        get: function (this: Vue) {
            return this[symbol];
        },
        set: function (this: Vue, value: unknown) {
            this[symbol] = value;
            this.$emit(name ? name : `update:${key}`, value);
        }
    }
});

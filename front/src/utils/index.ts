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

import { Moment } from "moment";
import cloneDeep from "lodash/cloneDeep"

export * from "./object"

let performance;

try {
    performance = (typeof window !== "undefined" && window.performance) || require("perf_hooks").performance;
} catch {
    console.log("no performance");
}

type Simplify<T> = string |
    T extends string   ? string :
    T extends number   ? number :
    T extends boolean  ? boolean :
    T extends Moment   ? string :
    T extends Array<infer K> ? Array<Simplify<K>>  :
    T extends (infer K)[] ? Simplify<K>[] :
    T extends Object ? Jsonified<T> : any;

export type Jsonified<T> = { [K in keyof T]: Simplify<T[K]> }
export type Optionalify<T> = { [K in keyof T]?: T[K] }
export type Dictionary<T> = { [key: string]: T };
export type Supplier<T> = T | (() => T);

export type Index = string | symbol | number;
export type FetchingState = 'fetching' | 'ready' | 'error' | 'not-initialized';

export type MakeOptional<T, K extends keyof T> = Optionalify<Pick<T, K>> & Omit<T, K>;

export interface Converter<T, U> {
    convert: (value: T) => U;
}

export interface TwoWayConverter<T, U> extends Converter<T, U> {
    convertBack: (value: U) => T;
}

export const identityConverter: TwoWayConverter<any, any> = {
    convert: (value) => value,
    convertBack: (value) => value,
}

export function signed(number: number): string {
    return number > 0 ? `+${number}` : number.toString();
}

export function ensureArray<T>(x: T[]|T): T[] {
    return x instanceof Array ? x : [ x ];
}

export function set(object: any, path: string, value: any) {
    const segments = path.split('.');
    while (segments.length > 1) {
        object = object[segments.shift()];
    }

    object[segments.shift()] = value;
}

export function get(object: any, path: string): any {
    const segments = path.split('.');
    while (segments.length > 1) {
        object = object[segments.shift()];
    }

    return object[segments.shift()];
}

export function distinct<T>(value: T, index: number, array: T[]) {
    return array.indexOf(value) === index;
}

export function time<T>(action: () => T, name?: string) {
    const start = performance.now();
    const result = action();
    console.debug(`${name || 'this'} operation took ${performance.now() - start}ms`);

    return result;
}

export const identity = a => a;

export function unique<T, U>(array: T[], criterion: (item: T) => U = identity) {
    const result: T[] = [];
    const known = new Set<U>();

    const entries = array.map(item => [ criterion(item), item ]) as [ U, T ][];

    for (const [ key, item ] of entries) {
        if (known.has(key)) {
            continue;
        }

        known.add(key);
        result.push(item);
    }

    return result;
}

export const supply: <T>(x: T) => () => T = x => () => cloneDeep(x);

type Pattern<TResult, TArgs extends any[]> = [
    (...args: TArgs) => boolean,
    ((...args: TArgs) => TResult) | TResult,
]

export function match<TResult, TArgs extends any[]>(...patterns: Pattern<TResult, TArgs>[]): (...args: TArgs) => TResult {
    return (...args: TArgs) => {
        for (let [pattern, action] of patterns) {
            if (pattern(...args)) {
                return typeof action === "function" ? (action as (...args: TArgs) => TResult)(...args) : action;
            }
        }

        throw new Error(`No pattern matches args: ${JSON.stringify(args)}`);
    }
}

match.default = (...args: any[]) => true;

export function resolve<T>(supplier: Supplier<T>): T {
    if (typeof supplier === "undefined") {
        return undefined;
    }

    return supplier instanceof Function ? supplier() : supplier;
}

export const delay = (milliseconds: number): Promise<void> => new Promise(resolve => setTimeout(resolve, milliseconds));

export function createBackoff(timeout: number): (counter: number, callback: () => void) => number {
    return (counter: number, callback: () => void): number => {
        const k = Math.round(2**(counter - 1) + Math.random() * (2**counter - 2**(counter - 1)));
        return setTimeout(callback, timeout * (Math.max(1, k))) as any;
    }
}

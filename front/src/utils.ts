import { Moment } from "moment";

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

export function map<T extends {}, KT extends keyof T, R extends { [KR in keyof T] }>(source: T, mapper: (value: T[KT], key: KT) => R[KT]): R {
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

export function choice<T>(array: T[]): T {
    return array[array.length * Math.random() | 0];
}

export function createBackoff(timeout: number): (counter: number, callback: () => void) => number {
    return (counter: number, callback: () => void): number => {
        const k = Math.round(2**(counter - 1) + Math.random() * (2**counter - 2**(counter - 1)));
        return window.setTimeout(callback, timeout * (Math.max(1, k)));
    }
}

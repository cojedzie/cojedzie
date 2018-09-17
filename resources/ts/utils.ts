type Simplify<T, K = any> = string |
    T extends string   ? string :
    T extends number   ? number :
    T extends boolean  ? boolean :
    T extends Array<K> ? Array<K>  :
    T extends Object   ? Object : any;

export type Jsonified<T> = { [K in keyof T]: Simplify<T[K]> }
export type Optionalify<T> = { [K in keyof T]?: T[K] }
export type Dictionary<T> = { [key: string]: T };

export type Index = string | symbol | number;
export type FetchingState = 'fetching' | 'ready' | 'error' | 'not-initialized';

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

export function signed(number: number): string {
    return number > 0 ? `+${number}` : number.toString();
}

export function ensureArray<T>(x: T[]|T): T[] {
    return x instanceof Array ? x : [ x ];
}
import { Dictionary } from "@/utils";

export type UrlParams = {
    [name: string]: any
}
type ParamValuePair = [string, string];

export function query(params: UrlParams = {}) {
    function* simplify(name: string, param: any): IterableIterator<ParamValuePair> {
        if (typeof param === 'string') {
            yield [name, param];
        } else if (typeof param === 'boolean') {
            if (param) {
                yield [name, '1'];
            }
        } else if (typeof param === 'number') {
            yield [name, param.toString()];
        } else if (param instanceof Array) {
            for (let entry of param) {
                yield* simplify(`${ name }[]`, entry);
            }
        } else if (typeof param === "object") {
            for (let [key, entry] of Object.entries(param)) {
                yield* simplify(`${ name }[${ key }]`, entry);
            }
        }
    }

    let simplified: ParamValuePair[] = [];
    for (const [key, entry] of Object.entries(params)) {
        for (const pair of simplify(key, entry)) {
            simplified.push(pair);
        }
    }

    return Object.values(simplified).map(entry => entry.map(encodeURIComponent).join('=')).join('&');
}

export function prepare(url: string, params: Dictionary<string> = {}) {
    const regex = /\{([\w-]+)\}/gi;

    let group;
    while (group = regex.exec(url)) {
        const name = group[1];

        url = url.replace(new RegExp(`\{${ name }\}`, 'gi'), params[name]);
        delete params[name];
    }

    return Object.keys(params).length > 0 ? `${ url }?${ query(params) }` : url;
}

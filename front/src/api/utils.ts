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

import { Dictionary } from "@/utils";

export type UrlParamSimpleValue = string | boolean | number;
export type UrlParamValue = UrlParamSimpleValue | UrlParamSimpleValue[] | Record<string, UrlParamSimpleValue>;
export type UrlParams = Record<string, UrlParamValue>;

type ParamValuePair = [string, string];

export function query(params: UrlParams = {}) {
    function* simplify(name: string, param: UrlParamValue): IterableIterator<ParamValuePair> {
        if (typeof param === "string") {
            yield [name, param];
        } else if (typeof param === "boolean") {
            if (param) {
                yield [name, "1"];
            }
        } else if (typeof param === "number") {
            yield [name, param.toString()];
        } else if (param instanceof Array) {
            for (const entry of param) {
                yield* simplify(`${name}[]`, entry);
            }
        } else if (typeof param === "object") {
            for (const [key, entry] of Object.entries(param)) {
                yield* simplify(`${name}[${key}]`, entry);
            }
        }
    }

    const simplified: ParamValuePair[] = [];
    for (const [key, entry] of Object.entries(params)) {
        for (const pair of simplify(key, entry)) {
            simplified.push(pair);
        }
    }

    return Object.values(simplified)
        .map(entry => entry.map(encodeURIComponent).join("="))
        .join("&");
}

export function prepare(url: string, params: Dictionary<string> = {}) {
    const regex = /\{([\w-]+)\}/gi;

    let group: RegExpExecArray;
    while ((group = regex.exec(url))) {
        const name = group[1];

        url = url.replace(new RegExp(`\\{${name}\\}`, "gi"), params[name]);
        delete params[name];
    }

    return Object.keys(params).length > 0 ? `${url}?${query(params)}` : url;
}

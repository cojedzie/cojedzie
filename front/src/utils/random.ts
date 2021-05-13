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

export function choice<T>(array: T[]): T {
    return array[array.length * Math.random() | 0];
}

export function normal(mean?: number, sigma?: number) {
    if (typeof mean === "undefined") {
        let u = 0, v = 0;

        while (u === 0) u = Math.random();
        while (v === 0) v = Math.random();

        return Math.sqrt( -2.0 * Math.log( u ) ) * Math.cos( 2.0 * Math.PI * v );
    } else {
        return mean + normal() * sigma;
    }
}

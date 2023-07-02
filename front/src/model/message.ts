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
import { Stop } from "@/model/stop";
import { Line } from "@/model/line";
import { Collection } from "@/model/common";

export type MessageType = "info" | "breakdown" | "unknown";

export interface MessageRefs {
    stops: Collection<Stop>;
    lines: Collection<Line>;
}

export interface Message {
    id: string;
    type: MessageType;
    message: string;
    validFrom?: Moment;
    validTo?: Moment;
    $refs: MessageRefs;
    $type: "vnd.cojedzie.message";
}

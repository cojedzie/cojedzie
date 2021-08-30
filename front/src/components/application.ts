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

import { createRouter, createWebHistory, RouteRecordRaw } from "vue-router";
import store from "@/store";
import { Options, Vue } from "vue-class-component";
import { createApp } from "vue";


const routes: RouteRecordRaw[] = [
    { path: "/:provider", component: () => import ("@/components/main") },
    { path: "/", component: () => import ("@/components/provider-chooser") },
]

export const router = createRouter({
    routes,
    history: createWebHistory(),
});

@Options({ template: require("@templates/app.html"), router, store })
export class Application extends Vue {

}

export const app = createApp(Application);

app.use(router);

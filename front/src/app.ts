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

import "vite/modulepreload-polyfill";

import "@styles/tailwind.css";
import "@styles/main.scss";

import { createPinia } from "pinia";
import { BrowserStorage } from "@/stores/persistence/browser";
import { persistence } from "@/stores/persistence/plugin";
import { dragscroll } from "vue-dragscroll";

import moment from "moment";
import "moment/dist/locale/pl";

moment.locale("pl");

import { app } from "@/components";
import filters from "@/filters";
import { install as api } from "@/api";
import { install as sentry } from "@/sentry";
import container from "@/services";
import { router } from "@/routes";

app.use(sentry);
app.use(api);
app.use(filters);
app.use(router);

app.directive("dragscroll", dragscroll);

// async dependencies
(async function () {
    // Pinia has to be active before the stores are created
    const pinia = createPinia();
    pinia.use(persistence());
    app.use(pinia);

    const { migrate } = await import("./stores/persistence/migrations");
    await migrate(new BrowserStorage(window.localStorage));

    const [{ useNetworkStore }, { useSelectionStore }, { bindClientContext }] = await Promise.all([
        import("@/stores/network"),
        import("@/stores/selection"),
        import("@/api/context"),
    ]);
    const network = useNetworkStore(pinia);
    const selection = useSelectionStore(pinia);
    bindClientContext({
        network: {
            available: () => network.available,
            update: () => network.update(),
            nodeFailed: id => network.nodeFailed(id),
        },
        provider: () => selection.provider?.id,
    });

    app.use(container);

    // saved stores load from the migrated keys, the app must not render with their defaults
    const [{ useFavouritesStore }, { useDeparturesSettingsStore }, { useMessagesSettingsStore }, { useHistoryStore }] =
        await Promise.all([
            import("@/stores/favourites"),
            import("@/stores/departures-settings"),
            import("@/stores/messages-settings"),
            import("@/stores/history"),
        ]);
    await Promise.all(
        [
            useFavouritesStore,
            useDeparturesSettingsStore,
            useMessagesSettingsStore,
            useHistoryStore,
            useSelectionStore,
        ].map(use => use(pinia).$hydrated),
    );

    // todo figure out better way
    const fragment = document.createDocumentFragment();
    const root = document.getElementById("root");
    app.mount(fragment as unknown as Element);
    root.parentNode.replaceChild(fragment, root);
})();

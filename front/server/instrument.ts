/*
 * Copyright (C) 2026 Kacper Donat
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

import * as Sentry from "@sentry/node";

// Sentry has to be initialised before express is loaded, so it can instrument it - import this file first.
// SENTRY_DSN is the browser's DSN (frontend-vue), the server reports to its own project.
if (process.env.SENTRY_SERVER_DSN) {
    Sentry.init({
        dsn: process.env.SENTRY_SERVER_DSN,
        release: process.env.COJEDZIE_VERSION,
        environment: process.env.SENTRY_ENVIRONMENT,
        tracesSampleRate: parseFloat(process.env.SENTRY_SERVER_SAMPLE_RATE || "0.01"),
        integrations: [Sentry.consoleLoggingIntegration({ levels: ["info", "warn", "error"] })],
    });
}

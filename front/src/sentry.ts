import { App } from "vue";
import * as Sentry from "@sentry/vue";
import { router } from "@/routes";

export function install(app: App) {
    // Skip if DSN for sentry is not defined
    if (!window.CoJedzie.sentry?.dsn) {
        return;
    }

    const config = window.CoJedzie.sentry;

    Sentry.init({
        app,
        dsn: config.dsn,

        integrations: [
            Sentry.browserTracingIntegration({ router }),
            Sentry.replayIntegration(),
            Sentry.consoleLoggingIntegration({ levels: ["warn", "error"] }),
        ],

        tracePropagationTargets: [window.CoJedzie.api.base, window.CoJedzie.api.hub, /^\//],

        release: window.CoJedzie.version,
        environment: config.environment,

        tracesSampleRate: config.tracesSampleRate ?? 0.1,

        replaysSessionSampleRate: config.replaysSessionSampleRate ?? 0.1,
        replaysOnErrorSampleRate: config.replaysErrorSampleRate ?? 1.0,
    });

    Sentry.setTags(config.tags || {});
}

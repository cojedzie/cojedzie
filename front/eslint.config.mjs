import js from "@eslint/js";
import prettier from "eslint-config-prettier";
import storybook from "eslint-plugin-storybook";
import vue from "eslint-plugin-vue";
import { defineConfig } from "eslint/config";
import globals from "globals";
import typescript from "typescript-eslint";

const temporalPolyfill = [{ name: "temporal-polyfill", message: "Import Temporal from @/time (ADR 0033)." }];

export default defineConfig([
    { ignores: ["**/build/**", "resources/**", "src/types/**/*.d.ts"] },

    js.configs.recommended,
    typescript.configs.recommended,
    vue.configs["flat/recommended"],
    storybook.configs["flat/recommended"],
    prettier,

    {
        languageOptions: {
            globals: { ...globals.browser, ...globals.node },
        },
    },
    {
        files: ["**/*.vue"],
        languageOptions: {
            parserOptions: { parser: typescript.parser },
        },
    },
    {
        rules: {
            "vue/html-indent": ["warn", 4],
            "vue/max-attributes-per-line": ["warn", { singleline: 4, multiline: 1 }],
            // the root component, never referred to by its name
            "vue/multi-word-component-names": ["error", { ignores: ["Application"] }],
            "@typescript-eslint/no-explicit-any": "warn",
            "@typescript-eslint/no-require-imports": "off",
            "@typescript-eslint/no-unused-vars": ["warn", { argsIgnorePattern: "^_" }],
            "@typescript-eslint/no-inferrable-types": ["warn", { ignoreProperties: true }],
            "@typescript-eslint/ban-ts-comment": "off",
        },
    },
    {
        // Temporal comes from @/time, the one place that decides between the polyfill and the native API
        files: ["src/**", "test/**", ".storybook/**"],
        ignores: ["src/time/temporal.ts"],
        rules: {
            "no-restricted-imports": ["error", { paths: temporalPolyfill }],
        },
    },
    {
        // framework-free logic, kept portable to another UI framework
        files: ["src/timetable/**", "src/time/**"],
        ignores: ["src/time/temporal.ts"],
        rules: {
            "no-restricted-imports": [
                "error",
                {
                    paths: ["vue", "pinia", "vue-router", "@/api", ...temporalPolyfill],
                    patterns: ["@vue/*", "@/stores/*", "@/composables/*", "@/components/*", "*.vue"],
                },
            ],
        },
    },
]);

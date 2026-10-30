import js from "@eslint/js";
import prettier from "eslint-config-prettier";
import storybook from "eslint-plugin-storybook";
import vue from "eslint-plugin-vue";
import { defineConfig } from "eslint/config";
import globals from "globals";
import typescript from "typescript-eslint";

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
]);

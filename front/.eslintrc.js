module.exports = {
    "root": true,
    "extends": [
        "eslint:recommended",
        "plugin:@typescript-eslint/recommended",
        "plugin:vue/vue3-recommended"
    ],
    "parser": "vue-eslint-parser",
    "parserOptions": { 
        "parser": "@typescript-eslint/parser",
        "source": "module"
    },
    "plugins": [
        "@typescript-eslint",
        "vue"
    ],
    "rules": {
        "vue/html-indent": [1, 4],
        "vue/max-attributes-per-line": [1, { singleline: 4, multiline: 1 }],
        "@typescript-eslint/no-var-requires": [ 0 ],
        "@typescript-eslint/no-unused-vars": [1, { argsIgnorePattern: "^_" }],
        "@typescript-eslint/no-inferrable-types": [1, { ignoreProperties: true }],
        "@typescript-eslint/ban-ts-comment": [0],
        "no-unused-vars": [1, { argsIgnorePattern: "^_" }],
    },
    "ignorePatterns": [".eslintrc.js", "webpack.config.js", "**/build/**", "resources/**/*", "src/types/**/*.d.ts"]
}

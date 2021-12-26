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
        "vue/max-attributes-per-line": [1, { singleline: 5, multiline: 1 }],
        "@typescript-eslint/no-var-requires": [ 0 ]
    },
    "ignorePatterns": [".eslintrc.js", "webpack.config.js", "**/build/**", "resources/**/*"]
}
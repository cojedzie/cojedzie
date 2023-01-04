module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'body-case': [2, 'always', 'sentence-case'],
        'subject-case': [2, 'always', 'sentence-case'],
        'body-max-line-length': [1, 'always', 80],
        'header-max-length': [1, 'always', 80],
        'type-enum': [2, 'always', [
            'build',
            'chore',
            'ci',
            'docs',
            'feat',
            'fix',
            'perf',
            'refactor',
            'revert',
            'security',
            'style',
            'test',
            'release'
        ]],
        'scope-enum': [2, 'always', ['front', 'api', 'streaming-parser']],
    },
    ignores: [
        commit => commit.startsWith('WIP ')
    ]
};

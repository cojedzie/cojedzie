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

const crypto = require('crypto');
const path = require('path');
const templateLoader = require.resolve('vue-loader/dist/templateLoader');

const hashes = new Map();

const exportWithRender = ({ id, hmr }) => `
export default function WithRender(decorated) { 
    decorated.render = render;
    ${hmr ? hotReloadRegister({ id }) : '/* hmr disabled */'}
    return decorated;
}
`;

const hotReloadRegister = ({ id }) => `
    decorated.__hmrId = '${id}';
    
    const componentModule = require.cache[module.parents[0]];
    if (componentModule.hot) {
        componentModule.hot.accept();
        if (!__VUE_HMR_RUNTIME__.createRecord('${id}', decorated)) {
            __VUE_HMR_RUNTIME__.reload('${id}', decorated);
        }
    }
`

const hotReloadRerender = ({ id }) => `
    if (module.hot) {
        module.hot.accept();
        __VUE_HMR_RUNTIME__.rerender('${id}', render);
    }
`

const importRenderer = renderFunctionResource => `import { render } from ${renderFunctionResource};`;
const exportRenderer = renderFunctionResource => `export { render } from ${renderFunctionResource};`;

const generateTemplateId = context => {
    const filename = path.relative(process.cwd(), context.resourcePath);

    if (!hashes.has(filename)) {
        const hash = crypto
            .createHash('sha256')
            .update(filename, "utf-8")
            .digest("hex")
            .substr(0, 12)

        hashes.set(filename, hash);
    }

    return hashes.get(filename);
}

module.exports = function vueTemplateLoader(source, map) {
    const context = this;
    const options = this.getOptions();

    const isServer = options.isServerBuild ?? context.target === 'node'
    const isProduction = context.mode === 'production' || process.env.NODE_ENV === 'production'

    const hmr = !isServer && !isProduction && options.hotReload !== false;

    const id = `data-v-${generateTemplateId(context)}`;

    const renderFunctionUrl = `!${templateLoader}?${JSON.stringify(options)}!${context.resourcePath}?id=${id}`;
    const renderFunctionResource = JSON.stringify(context.utils.contextify(context.context || context.rootContext, renderFunctionUrl));

    return [
        importRenderer(renderFunctionResource),
        exportRenderer(renderFunctionResource),
        exportWithRender({ id, hmr }),
        hmr ? hotReloadRerender({ id }) : '/* hmr disabled */',
    ].join("\n");
};

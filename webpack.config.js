const path = require('path');
const config = {
    entry: {
        main: ['./resources/ts/app.ts'],
    },
    output: {
        path: path.resolve('./public/'),
        filename: "bundle.js",
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js'],
        alias: {
          'vue$': 'vue/dist/vue.esm.js'
        }
    },
    module: {
        rules: [{
            test: /\.svg$/,
            include: [
                path.resolve('./resources/icons')
            ],
            use: ['raw-loader', {
                loader: path.resolve('./resources/svg-icon-loader.js')
            }]
        },{
            test: /\.s[ac]ss$/,
            use: ["style-loader", "css-loader?sourceMap", "sass-loader?sourceMap"]
        }, {
            test: /\.css$/,
            use: ["style-loader", "css-loader"]
        }, {
            test: /\.tsx?$/,
            use: 'ts-loader',
            exclude: /node_modules/
        }, {
            test: /\.(png|svg|jpg|gif)$/,
            use: 'file-loader',
            exclude: [
                path.resolve('./resources/icons')
            ]
        }, {
            test: /\.(woff|woff2|eot|ttf|otf)$/,
            use: 'file-loader'
        }, {
            test: /\.html?$/,
            use: 'raw-loader'
        }]
    },
};

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'inline-source-map';
    }

    return config;
};
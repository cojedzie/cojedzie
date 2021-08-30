const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const { GenerateSW } = require('workbox-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');

const output_dir = path.resolve('./build/')

const config = {
    entry: {
        main: ['./src/app.ts'],
        api: ['./styles/api.scss']
    },
    output: {
        path: path.join(output_dir, './public/dist/'),
        publicPath: "/dist/",
        filename: "[name].js",
        chunkFilename: '[name].[chunkhash:8].js'
    },
    resolve: {
        extensions: ['.tsx', '.ts', '.js'],
        alias: {
            'vue$': 'vue/dist/vue.esm-bundler.js',
            'mapbox-gl$': 'mapbox-gl/dist/mapbox-gl-unminified',
            "@templates": path.resolve(__dirname, "./templates"),
            "@resources": path.resolve(__dirname, "./resources"),
            "@styles": path.resolve(__dirname, "./styles"),
            "@": path.resolve(__dirname, "./src"),
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
        }, {
            test: /\.s[ac]ss$/,
            use: [{
                loader: MiniCssExtractPlugin.loader,
            }, "css-loader?sourceMap&url=false", "sass-loader?sourceMap&url=false"]
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
            use: 'raw-loader',
            exclude: [
                path.resolve('./resources/index.html')
            ]
        }]
    },
    plugins: [
        new MiniCssExtractPlugin({ filename: '[name].css' }),
        new CopyWebpackPlugin([{ from: './resources/images/', to: '../images/', ignore: ['*.ai'] }]),
        new ImageminPlugin({ test: /\.(jpe?g|png|gif|svg)$/i }),
        new GenerateSW({
            navigationPreload: true,
            runtimeCaching: [{
                urlPattern: ({ event }) => event.request.mode === 'navigate',
                handler: 'NetworkFirst',
            }, {
                urlPattern: /^https?:\/\/api\.maptiler\.com\//,
                handler: 'CacheFirst',
            }],
            swDest: '../service-worker.js'
        }),
    ]
};

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'inline-source-map';
    }

    return config;
};

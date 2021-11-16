const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const { GenerateSW } = require('workbox-webpack-plugin');
const { VueLoaderPlugin } = require('vue-loader')
const webpack = require("webpack");

const outputDir = path.resolve('./build/')

module.exports = (env, argv) => {
    const config = {
        entry: {
            main: ['./src/app.ts', './styles/main.scss'],
            api: ['./styles/api.scss'],
        },
        output: {
            path: path.join(outputDir, './public/dist/'),
            publicPath: "/dist/",
            filename: "[name].js",
            chunkFilename: '[name].[chunkhash:8].js'
        },
        resolve: {
            extensions: ['.tsx', '.ts', '.js'],
            alias: {
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
                use: [
                    { loader: path.resolve('./resources/svg-icon-loader.js') }
                ],
                type: 'asset/source'
            }, {
                test: /\.s[ac]ss$/,
                use: [
                    argv.mode === 'production' ? MiniCssExtractPlugin.loader : 'style-loader',
                    "css-loader",
                    "sass-loader"
                ]
            }, {
                test: /\.css$/,
                use: ["style-loader", "css-loader"]
            }, {
                test: /\.vue$/,
                loader: "vue-loader",
                options: {
                    enableTsInTemplate: false,
                }
            }, {
                test: /\.tsx?$/,
                loader: "ts-loader",
                options: {
                    appendTsSuffixTo: [/\.vue$/],
                },
                exclude: /node_modules/,
            }, {
                test: /\.(png|svg|jpg|gif)$/,
                type: 'asset/resource',
                exclude: [
                    path.resolve('./resources/icons')
                ]
            }, {
                test: /\.(woff|woff2|eot|ttf|otf)$/,
                type: 'asset/resource',
            }, {
                test: /\.html?$/,
                use: [
                    {
                        loader: path.resolve('./resources/vue-template-loader.js'),
                        options: {
                            compilerOptions: {
                                whitespace: "preserve",
                            }
                        }
                    },
                ],
                include: [
                    path.resolve('./templates')
                ]
            }]
        },
        plugins: [
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({ filename: '[name].css' }),
            new CopyWebpackPlugin({
                patterns: [
                    {
                        from: path.resolve('./resources/images/**/*'),
                        to: '../images/[name][ext]',
                        globOptions: {
                            gitignore: true,
                            ignore: ["**/*.kra", "**/*.ai", "**/*~"]
                        }
                    }
                ]
            }),
            new ImageminPlugin({ test: /\.(jpe?g|png|gif|svg)$/i }),
            new webpack.DefinePlugin({
                __IS_SSR__: false,
                __VUE_OPTIONS_API__: true,
                __VUE_PROD_DEVTOOLS__: false,
            }),
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

    if (argv.mode === 'development') {
        config.mode = 'development';
        config.devtool = 'inline-source-map';

        config.entry.main.push('webpack-hot-middleware/client')
        config.plugins.push(new webpack.HotModuleReplacementPlugin())
    }

    return config;
};

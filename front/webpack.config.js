const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const {GenerateSW} = require('workbox-webpack-plugin');
const {VueLoaderPlugin} = require('vue-loader')

const outputDir = path.resolve('./build/')

const config = {
    entry: {
        main: ['./src/app.ts', './styles/main.scss'],
        api: ['./styles/api.scss']
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
            use: [
                { loader: path.resolve('./resources/vue-template-loader.js') },
                {
                    loader: require.resolve('vue-loader/dist/templateLoader'),
                    options: {
                        compilerOptions: {
                            whitespace: "preserve",
                        }
                    }
                },
            ],
            exclude: [
                path.resolve('./resources/index.html')
            ]
        }, {
            test: /\.vue$/,
            use: "vue-loader"
        }]
    },
    plugins: [
        new VueLoaderPlugin(),
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

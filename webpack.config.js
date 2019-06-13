const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const HtmlWebpackPlugin = require("html-webpack-plugin");
const fs = require('fs');
const glob = require('glob');
const isDevel = false;
const jsPublicPath = "/js/etatmssync/";
const cssPublicPath = "/css/etatmssync/";

class CleanOldFiles {
    removeOldFiles(clearDirPath, extensions = ['css', 'js']) {
        if (typeof (extensions) != 'object') {
            throw new Error("Failed extension format must be array of string");
        }
        for (const i in extensions) {
            const extension = extensions[i].replace(".", "");
            const findFileTemplate = `${clearDirPath}/*\.${extension}`;
            const files = glob.Glob(findFileTemplate, (er, files) => {
                files.forEach(file => {
                    try {
                        if (fs.statSync(file)) {
                            fs.unlinkSync(file);
                            console.log(`${file} removed`);
                        }
                    } catch (er) {
                        // console.error(er.message);
                    }
                })
            });
        }
    }
    apply(compiler){
        // compiler.hooks.beforeRun.tapAsync('CleanOldFiles',(compilation, callback)=>{})
        compiler.hooks.beforeCompile.tapAsync('CleanOldFilesBC',(compilation, callback)=>{
            if(compiler.outputPath){
                this.removeOldFiles(compiler.outputPath, ['js','css']);
                this.removeOldFiles(path.join(__dirname, "css"), ['js','css']);
            }
            callback();
        })
    }
}

const mode = isDevel ? "development" : "production"
module.exports = {
    mode: mode,
    entry:  {
        tariff: "./src/js/tariffs.js",
        config: "./src/js/config.js",
    },
    output: {
      path: __dirname + "/js/",
      publicPath: "/js/",
      filename: '[chunkhash].js'
    },
    stats: {
        assets: true,
        builtAt: true,
        colors: true,
        children: false
    },
    module: {
      rules: [
        {
            exclude: /\/node_modules/,
            test: /\.js$/,
            loader: "babel-loader",
            options: {
                presets: ['@babel/env'] ,
            },
        },
        {
            test: /\.tmpl.html$/,
            loader: 'raw-loader'
        },
        {
            test: /\.(sc|as|c)ss$/,
            use: [
                {
                    loader: MiniCssExtractPlugin.loader,
                },
                "css-loader",
                "sass-loader"
            ]
        }
      ]
    },
    optimization: {
        minimize: true,
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                // default: {
                //     enforce: true,
                //     priority: 1,
                //     name: '[chunkhash]'
                // },
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: 2,
                    name: 'v',
                    enforce: true,
                    chunks: 'all'
                }
            }
        }
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: "../css/[chunkhash].css",
            chunkFilename: "../css/[chunkhash].css"
        }),
        new HtmlWebpackPlugin({
            jsPublicPath: jsPublicPath,
            cssPublicPath: cssPublicPath,
            xhtml: true,
            showErrors: false,
            minify: {
                collapseWhitespace: true
            },
            inject: false,
            excludeChunks: ['tariff'],
            template: path.join(__dirname, 'src','templates','etatmssyncconfig.html'),
            filename: path.join(__dirname, 'templates','etatmssyncconfig.html')

        }),
        new HtmlWebpackPlugin({
            jsPublicPath: jsPublicPath,
            cssPublicPath: cssPublicPath,
            xhtml: true,
            showErrors: false,
            minify: {
                collapseWhitespace: true
            },
            inject: false,
            excludeChunks: ['config'],
            template: path.join(__dirname, 'src', 'templates', 'etatmssynctariffs.html'),
            filename: path.join(__dirname, 'templates', 'etatmssynctariffs.html')
        }),
        new CleanOldFiles()
    ]
}

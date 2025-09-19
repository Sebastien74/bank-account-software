/**
 * WEBPACK
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 *   1 - back
 *   2 - security
 *   3 - module.exports
 */

const Encore = require('@symfony/webpack-encore');
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

const path = require('path');

const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');

const enableNotification = false;
const enableSourceMaps = !Encore.isProduction();
const enableVersioning = true; // else Encore.isProduction()
const enableIntegrity = true; // else Encore.isProduction()
const target = 'web';
const cache = Encore.isProduction();
const parallelism = 4;
const concatenateModules = false;
const providedExports = false;
const usedExports = false;
const removeEmptyChunks = true; // else Encore.isProduction()
const mergeDuplicateChunks = true; // else Encore.isProduction()
const sideEffects = true; // else Encore.isProduction()
const splitChunks = {chunks: 'async'};
const minimize = Encore.isProduction();

/** 1 - back */

Encore.reset();

Encore.setOutputPath('public/build/back')
    .setPublicPath('/build/back')
    .addEntry('back-vendor', './assets/js/back/vendor.js')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .autoProvideVariables({
        moment: 'moment'
    })
    .copyFiles({
        from: './assets/medias/images/back',
        to: 'images/theme/[path][name].[hash:8].[ext]'
    })
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.33'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader()
    .autoProvidejQuery();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const back = Encore.getWebpackConfig();
back.name = 'back';
back.target = target;
back.cache = cache;
back.parallelism = parallelism;
back.optimization.concatenateModules = concatenateModules;
back.optimization.providedExports = providedExports;
back.optimization.usedExports = usedExports;
back.optimization.removeEmptyChunks = removeEmptyChunks;
back.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
back.optimization.sideEffects = sideEffects;
back.optimization.splitChunks = splitChunks;
back.optimization.minimize = minimize;
back.resolve.extensions.push('json');
if (back.optimization && back.optimization.minimizer) {
    back.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 2 - security */

Encore.reset();

Encore.setOutputPath('public/build/security')
    .setPublicPath('/build/security')
    .addEntry('security', './assets/js/security/vendor.js')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(enableSourceMaps)
    .enableVersioning(enableVersioning)
    .enableIntegrityHashes(enableIntegrity)
    .copyFiles({
        from: './assets/medias/images/security',
        to: 'images/[path][name].[hash:8].[ext]'
    })
    .configureBabel(function (babelConfig) {
        babelConfig.presets.push('@babel/preset-flow');
    }, {})
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.33'
    })
    .enablePostCssLoader((options) => {
        options.postcssOptions = {
            config: path.resolve(__dirname, "postcss.config.js")
        };
    })
    .configureImageRule({
        type: 'asset',
        maxSize: 8 * 1024, /** 8 kb - the default is 8kb */
    })
    .configureFontRule({
        type: 'asset',
        maxSize: 8 * 1024
    })
    .splitEntryChunks()
    .configureSplitChunks(function (splitChunks) {
        splitChunks.chunks = 'all'; // Tous les types de chunks
        splitChunks.minSize = 20000; // Taille minimale d'un chunk
        splitChunks.maxSize = 250000; // Taille maximale d'un chunk
        splitChunks.maxAsyncRequests = 30;
        splitChunks.maxInitialRequests = 30;
        splitChunks.enforceSizeThreshold = 50000;
    })
    .addPlugin(new CleanWebpackPlugin())
    .enableSingleRuntimeChunk()
    .enableSassLoader();

if (enableNotification) {
    Encore.enableBuildNotifications();
}

const security = Encore.getWebpackConfig();
security.name = 'security';
security.target = target;
security.cache = cache;
security.parallelism = parallelism;
security.optimization.concatenateModules = concatenateModules;
security.optimization.providedExports = providedExports;
security.optimization.usedExports = usedExports;
security.optimization.removeEmptyChunks = removeEmptyChunks;
security.optimization.mergeDuplicateChunks = mergeDuplicateChunks;
security.optimization.sideEffects = sideEffects;
security.optimization.splitChunks = splitChunks;
security.optimization.minimize = minimize;
security.resolve.extensions.push('json');
if (security.optimization && security.optimization.minimizer) {
    security.optimization.minimizer.push(new CssMinimizerPlugin());
}

/** 3 - module.exports */
module.exports = [back, security];
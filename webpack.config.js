var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .autoProvidejQuery()
    .autoProvideVariables({
        "window.Bloodhound": require.resolve('bloodhound-js'),
        "jQuery.tagsinput": "bootstrap-tagsinput",
        'window.jQuery': 'jquery'
    })
    .enableSassLoader()
    .enableVersioning()
    .addEntry('js/app', './assets/js/app.js')
    .addEntry('image/background_contract', './assets/image/background_contract.jpg')
    .addEntry('image/google_calendar_ical.png', './assets/image/google_calendar_ical.png')
    .addEntry('image/outlook.com_ical.png', './assets/image/outlook.com_ical.png')
    .addEntry('js/login', './assets/js/login.js')
    .addEntry('js/admin', './assets/js/admin.js')
    .addEntry('js/search', './assets/js/search.js')
    .addStyleEntry('css/app', ['./assets/scss/app.scss'])
    .addStyleEntry('css/admin', ['./assets/scss/admin.scss'])
    .splitEntryChunks()
    .disableSingleRuntimeChunk()
;

module.exports = Encore.getWebpackConfig();

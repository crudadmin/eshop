const mix = require('laravel-mix'),
      config = require('./config.js');

mix.js('js/app.js', 'dist/js').vue({ version: 2 });
mix.sass('scss/eshop.scss', 'dist/css');

for ( key in config.paths )
{
    mix.copy('./dist/js/app.js', config.paths[key] + '/js/app.js')
       .copy('./dist/css/eshop.css', config.paths[key] + '/css/eshop.css');
}

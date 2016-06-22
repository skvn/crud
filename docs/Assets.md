# Assets

## Vendor assets

Vendor assets are managed with [bower](!http://bower.io/) for packages and [gulp](!http://gulpjs.com/) for building minified versions of javascript and css files.

sudo apt-get install node
sudo apt-get install npm
sudo npm -g install bower

To manage assets first you need to install npm packages: run  `npm install` from the project directory.

When bower and other packages are installed, run `bower install` to download the packages. They are saved to `resources/bower_components` directory as configured in `.bowerrc`

After all the dependencies are downloaded you can build release assets with `gulp` command.

The build process is configured in `gulpfile.js` and `gulp-config.json` files. It runs several tasks.

- Combine  and minified all vendor styles to the `vendor.min.css` file
- Copy all the vendor assets images to the public directory
- Copy all the vendor fonts to the public directory
- Combine  and minified all vendor javascript modules and plugins to the `vendor.min.js` file
- Combine  and minified all vendor javascript localization files to the public directory


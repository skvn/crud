var config = require('./gulp-config.json');
var gulp = require('gulp');
var cleanCSS = require('gulp-clean-css');
var concat = require('gulp-concat');
var cssmin = require('gulp-cssmin');
var uglify = require('gulp-uglify');

//gulp.task('build',['css','css_images','css_fonts','fonts','js','js_locales']);

gulp.task('css', function() {
    return gulp
        .src(config.paths.vendor_css)
        .pipe(concat('vendor.min.css'))
        .pipe(cssmin())
        .pipe(gulp.dest(config.paths.dist_css));
});

gulp.task('css_images', function() {
    return gulp
        .src(config.paths.vendor_css_images)
        .pipe(gulp.dest(config.paths.dist_css));
});

gulp.task('css_fonts', function() {
    for (i in config.paths.vendor_css_fonts)
    {
        gulp
            .src(config.paths.vendor_css_fonts[i]+'/*')
            .pipe(gulp.dest(config.paths.dist_css+'/font'));
    }

});

gulp.task('fonts', function() {
    for (i in config.paths.vendor_fonts)
    {
        gulp
            .src(config.paths.vendor_fonts[i]+'/*')
            .pipe(gulp.dest(config.paths.dist_fonts));
    }
    return true;
});

gulp.task('js', function() {
    return gulp
        .src(config.paths.vendor_js)
        .pipe(concat('vendor.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(config.paths.dist_js));
});

gulp.task('js_locales', function() {

    for (lang in config.paths.vendor_js_locales)
    {
        gulp
            .src(config.paths.vendor_js_locales[lang])
            .pipe(concat(lang+'.min.js'))
            .pipe(gulp.dest(config.paths.dist_js_locales));
    }

});


//var elixir = require('laravel-elixir');
//elixir.extend('sourcemaps', false);

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Less
 | file for our application, as well as publishing vendor resources.
 |
 */


//elixir(function(mix) {
//	mix
//		.less('admin.less', 'public/css');

	///**************************************************************
	// * Libraries
	// **************************************************************/
	//mix
	//	.scripts([
	//		'jquery/js/jquery.min.js',
	//		'bootstrap/js/bootstrap.js',
	//		'noty/js/jquery.noty.packaged.js',
	//		'bootbox.js/js/bootbox.js',
	//		'jquery-colorbox/js/jquery.colorbox-min.js',
	//		'moment/js/moment-with-locales.min.js',
	//		'underscore/js/underscore.js',
	//		'x-editable/js/bootstrap-editable.min.js',
	//		'../../../resources/assets/js/core.js'
	//	], 'public/default/js/libraries.js', 'public/default/libs');
    //
	///**************************************************************
	// * Admin
	// **************************************************************/
	//mix
	//	.scripts([
	//		'column/filter/base.js',
	//		'column/filter/range.js',
	//		'column/filter/select.js',
	//		'column/filter/text.js',
	//		'column/checkbox.js',
	//		'column/control.js',
	//		'column/image.js',
	//		'form/datetime.js',
	//		'form/select.js',
	//		'form/image/init.js',
	//		'form/image/initMultiple.js',
	//		'init.js'
	//	], 'public/default/js/admin-default.js');
    //
	//mix
	//	.scripts([
	//		'libs/datatables/dataTables.bootstrap.js',
	//		'libs/datatables/datatables.js'
	//	], 'public/default/js/datatables.min.js');
//});
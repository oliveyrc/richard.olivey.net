// https://gist.github.com/jeromecoupe/0b807b0c1050647eb340360902c3203a
"use strict";

// Load plugins
//const autoprefixer = require("autoprefixer");
//const cssnano = require("cssnano");
//const eslint = require("gulp-eslint");
const gulp = require("gulp");
//const gutil = require('gulp-util');
const plumber = require("gulp-plumber");
//const postcss = require("gulp-postcss");
const sass = require("gulp-sass")(require('sass'));
//const bulkSass = require("gulp-sass-glob-import");
//const sourcemaps = require('gulp-sourcemaps');
const browserSync = require('browser-sync').create();
//const uglify = require('gulp-uglify');

// CSS task
function css() {
  return gulp
    .src(['./src/scss/**/*.scss'])
  //  .pipe(sourcemaps.init())
    .pipe(plumber())
    //.pipe(bulkSass())
    .pipe(sass({
      outputStyle: "compressed",
      lineNumbers: false,
      //loadPath: './css/*',
      sourceMap: true,
      sourceComments: 'map'
    }))
    .on('error', function (error) {
      gutil.log(error);
      this.emit('end');
    })
    .pipe(gulp.dest("./css/"))
    //.pipe(postcss([autoprefixer(), cssnano()]))
    //.pipe(sourcemaps.write('./css/maps'))
    //.pipe(gulp.dest("./css/"))
    .pipe(browserSync.stream())
    ;
}

// Watch files
function watchFiles() {

  // Setup a browsersync server.
  browserSync.init({
    proxy: 'http://richard-olivey-net.lndo.site',
    //injectChanges: true,
    // socket: {
    //   domain: 'https://richard-olivey-net.lndo.site',
    //   port: 80
    // },
    // socket: {
    //   domain: 'https://bs.richard-olivey-net.lndo.site',
    //   port: 80
    // },
    // open: false,
    // online: true,
    //logLevel: "debug",
    //logConnections: true,
  });
  gulp.watch(['./src/scss/**/*.scss'], gulp.parallel(css));
 // gulp.watch("./src/js/*", gulp.series(scripts));
}

//const js = gulp.series(scripts);
const watch = gulp.parallel(watchFiles);

// export tasks
exports.css = css;
//exports.js = js;
exports.watch = watch;
exports.default = watch;

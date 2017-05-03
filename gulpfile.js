var gulp = require('gulp'),
  sass = require('gulp-sass'),
  sassGlob = require('gulp-sass-glob'),
  autoPrefixer = require('gulp-autoprefixer');

gulp.task('sass', function(){
  return gulp.src('scss/**/[^_]*.scss')
    .pipe(sassGlob())
    .pipe(sass().on('error', sass.logError))
    .pipe(autoPrefixer({
      browsers: ['last 3 versions'],
      cascade: false
    }))
    .pipe(gulp.dest('css'))
});

gulp.task('watch', function() {
  gulp.watch('scss/**/*.scss', ['sass']);
});
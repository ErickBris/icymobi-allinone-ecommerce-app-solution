/**
 * Copyright © 2016 Inspius. All rights reserved.
 * Author: Stephen Pham, Phong Nguyen
 * Author URI: http://inspius.com
 */

'use strict';

module.exports = function(grunt) {
	var deploy = {
        watch: {
            sass: {
                files: 'assets/sass/*.scss',
                tasks: ['sass:style']
            }
        },
        sass: {
            style: {
                options: {
                    outputStyle: 'compressed',
                    precision: 10,
                },
                files: [{
                    src: 'assets/sass/style.scss',
                    dest: 'assets/css/style.css',
                }]
            }
        },
        browserSync: {
            files: [
               'assets/css/*.css',
               'assets/js/*.js',
            ],
            options: {
                watchTask: true,
                force: true,
                proxy: 'http://localhost/wordpress/isem/wp-admin/admin.php?page=icymobi-config',
                port: 8080,
                ui: {
                    port: 8080
                }
            }
        }
	};

	grunt.initConfig(deploy);
	
	grunt.loadNpmTasks('grunt-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-browser-sync');


	grunt.registerTask('start', [
		'browserSync',
		'watch'
	]);
};

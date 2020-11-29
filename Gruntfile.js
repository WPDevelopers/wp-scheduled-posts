module.exports = function (grunt) {
    'use strict'

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        addtextdomain: {
            options: {
                textdomain: 'wp-scheduled-posts',
            },
            update_all_domains: {
                options: {
                    updateDomains: true,
                },
                src: [
                    '*.js',
                    '*.php',
                    '**/*.php',
                    '!.git/**/*',
                    '!bin/**/*',
                    '!node_modules/**/*',
                    '!tests/**/*',
                ],
            },
        },

        makepot: {
            target: {
                options: {
                    domainPath: '/languages',
                    exclude: ['.git/*', 'bin/*', 'node_modules/*', 'tests/*'],
                    mainFile: 'wp-scheduled-posts.php',
                    potFilename: 'wp-scheduled-posts.pot',
                    potHeaders: {
                        poedit: true,
                        'x-poedit-keywordslist': true,
                    },
                    type: 'wp-plugin',
                    updateTimestamp: true,
                },
            },
        },
    })

    grunt.loadNpmTasks('grunt-wp-i18n')
    grunt.registerTask('default', ['i18n'])
    grunt.registerTask('i18n', ['addtextdomain', 'makepot'])

    grunt.util.linefeed = '\n'
}

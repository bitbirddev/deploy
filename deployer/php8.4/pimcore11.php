<?php

namespace Deployer;

########## REQUIRE PACKAGES ############################################
require 'contrib/rsync.php';
require 'recipe/pimcore.php';
require 'contrib/php-fpm.php';

########## SET CONFIG OPTION ###########################################
set('bin/php', 'php8.4');
set('php_fpm_version', '8.4');
set('keep_releases', 2);
set('writable_dirs', ['var', 'var/cache', 'var/cache/dev', 'var/cache/prod', 'var/log', 'var/sessions', 'public/var']);
set('ssh_multiplexing', true);
add('rsync', [ 'exclude' => [ '.git', 'node_modules', '.github', 'deploy.php', ], ]);


########## TASKS #######################################################
task('composer:post-update-cmd', function () {
    run('cd {{release_or_current_path}} && {{bin/composer}} run-script post-update-cmd 2>&1');
})->desc('clears cache, executes Migrations if any and installs Symlinks for Bundles');

task('pimcore:cache:clear', function () {
    run('{{bin/console}} pimcore:cache:clear');
})->desc("Clear Pimcore Cache");

task('symfony:cache:clear', function () {
    run('{{bin/console}} cache:clear');
})->desc("Clear Symfony Cache");

task('symfony:stop-workers', function () {
    run('{{bin/console}} messenger:stop-workers');
})->desc('Restarting Queue Workers');

########## HOOKS #######################################################
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'php-fpm:reload');

########## HOOKS #######################################################
task('deploy', [
    'deploy:info',
    'deploy:setup',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:writable',
    'symfony:cache:clear',
    'composer:post-update-cmd',
    'pimcore:rebuild-classes',
    'pimcore:cache:clear',
    'symfony:stop-workers',
    'deploy:publish',
])->desc('Deploy the application');

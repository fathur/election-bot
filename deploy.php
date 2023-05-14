<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:fathur/election-bot.git');

// Hosts

host('production')
    ->set('hostname', 'ec2-108-137-1-118.ap-southeast-3.compute.amazonaws.com')
    ->set('remote_user', 'ubuntu')
    ->set('identity_file', '~/.ssh/prod.pem')
    ->set('deploy_path', '/codes/election-bot-dep')
    ->set('http_user', 'ubuntu');

// Hooks

after('deploy:failed', 'deploy:unlock');



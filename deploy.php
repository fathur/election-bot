<?php
namespace Deployer;

require 'recipe/laravel.php';

// Config

set('repository', 'git@github.com:fathur/election-bot.git');

// Hosts

host('production')
    ->set('hostname', 'pemilu-kita.prod')
    ->set('deploy_path', '/codes/election-bot-dep')
    ->set('http_user', 'ubuntu');

// Hooks

after('deploy:failed', 'deploy:unlock');



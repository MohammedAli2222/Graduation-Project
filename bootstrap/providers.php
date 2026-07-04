<?php

use App\Providers\AppServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\RepositoryServiceProvider;


return [
    AppServiceProvider::class,
    TelescopeServiceProvider::class,
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
];

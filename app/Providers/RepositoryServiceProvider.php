<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\BrowseStoreRepository;
use App\Repositories\Contracts\BrowseStoreRepositoryInterface;
use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\TreatmentRepositoryInterface;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Repositories\Contracts\StudentMarketplaceRepositoryInterface;
use App\Repositories\Hod\TreatmentRepository;
use App\Repositories\Hod\CaseTypeRepository;
use App\Repositories\Hod\InstructorRepository;
use App\Repositories\StudentMarketplaceRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            TreatmentRepositoryInterface::class,
            TreatmentRepository::class
        );

        $this->app->bind(
            CaseTypeRepositoryInterface::class,
            CaseTypeRepository::class
        );

        $this->app->bind(
            InstructorRepositoryInterface::class,
            InstructorRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\ProductRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\OrderRepositoryInterface::class,
            \App\Repositories\OrderRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\PromotionRepositoryInterface::class,
            \App\Repositories\PromotionRepository::class
        );
        $this->app->bind(
            \App\Repositories\Contracts\CartRepositoryInterface::class,
            \App\Repositories\CartRepository::class
        );

        $this->app->bind(
            BrowseStoreRepositoryInterface::class,
            BrowseStoreRepository::class
        );
        $this->app->bind(
            StudentMarketplaceRepositoryInterface::class,
            StudentMarketplaceRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\FcmTokenRepositoryInterface::class,
            \App\Repositories\FcmTokenRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\NotificationRepositoryInterface::class,
            \App\Repositories\NotificationRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}

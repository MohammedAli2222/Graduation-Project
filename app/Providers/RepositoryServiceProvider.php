<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\BrowseStoreRepository;
use App\Repositories\Contracts\BrowseStoreRepositoryInterface;
use Illuminate\Support\ServiceProvider;

// استيراد الواجهات (Interfaces)
use App\Repositories\Contracts\TreatmentRepositoryInterface;
use App\Repositories\Contracts\CaseTypeRepositoryInterface;
use App\Repositories\Contracts\InstructorRepositoryInterface;
use App\Repositories\Contracts\StudentMarketplaceRepositoryInterface;
// استيراد المستودعات (Eloquent Repositories)
use App\Repositories\hod\TreatmentRepository;
use App\Repositories\hod\CaseTypeRepository;
use App\Repositories\hod\InstructorRepository;
use App\Repositories\StudentMarketplaceRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * تسجيل خدمات التطبيق وربط الواجهات بتنفيذاتها.
     *
     * @return void
     */
    public function register(): void
    {
        // ربط مستودع المعالجات
        $this->app->bind(
            TreatmentRepositoryInterface::class,
            TreatmentRepository::class
        );

        // ربط مستودع أنواع الحالات
        $this->app->bind(
            CaseTypeRepositoryInterface::class,
            CaseTypeRepository::class
        );

        // ربط مستودع المعيدين
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
    }

    /**
     * تمهيد أي خدمات إضافية للتطبيق.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

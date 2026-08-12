<?php

use App\Http\Middleware\EnsureCoursesAreSetup;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'ensure.courses.setup' => EnsureCoursesAreSetup::class,
        ]);
    })
    // الإشعارات معطّلة مؤقتاً: كل المستمعين في app/Listeners هم مستمعو إشعارات Firebase،
    // وبما أن FIREBASE_CREDENTIALS لم يُضبط بعد فإن تحميلهم يرمي استثناءً. تعطيل الاكتشاف
    // التلقائي يمنع تسجيلهم أصلاً، فتبقى الأحداث تُطلق بلا أي أثر جانبي.
    //
    // لإعادة تفعيل الإشعارات بعد ضبط Firebase: بدّل false إلى true هنا.
    // (لا يمكن ربطها بمتغيّر من .env لأن هذا السطر يُنفَّذ قبل تحميل ملف .env أصلاً)
    ->withEvents(discover: false)
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedException $e, Request $request) {

            return response_error(null, 403, 'Unauthorized Access');
        });
    })->create();

<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * الأدوار الأساسية في النظام. أضفنا دور "admin" لصلاحيات الإشراف العام
     * رغم أنه لا يوجد له بروفايل مخصص حالياً (لا يُستخدم في أي Route Middleware بعد)،
     * لأنه دور شائع الحاجة إليه مستقبلاً لأغراض الصيانة والدعم الفني.
     */
    private const ROLES = [
        'admin',
        'receptionist',
        'instructor',
        'student',
        'department_head',
        'store_owner',
    ];

    /**
     * الصلاحيات الدقيقة (Permissions) المستخدمة فعلياً داخل الكود
     * (مثال: DepartmentHeadService::delegateViewTreatmentsToInstructor ينشئ
     * صلاحية view-department-treatments ديناميكياً، لذلك ننشئها هنا مسبقاً
     * حتى تكون بيانات الـ Seed متكاملة ومطابقة للواقع).
     */
    private const PERMISSIONS = [
        'view waiting list',
        'view-department-treatments',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        foreach (self::PERMISSIONS as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // منح الاستقبال صلاحية الاطلاع على قائمة الانتظار
        Role::findByName('receptionist')->givePermissionTo('view waiting list');

        // منح رئيس القسم صلاحية استعراض معالجات قسمه افتراضياً
        Role::findByName('department_head')->givePermissionTo('view-department-treatments');

        // المدير العام يملك كل الصلاحيات دون الحاجة لتعدادها يدوياً
        Role::findByName('admin')->givePermissionTo(Permission::all());
    }
}

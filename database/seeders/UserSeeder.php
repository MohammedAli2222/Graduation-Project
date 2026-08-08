<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * يُنشئ جميع حسابات المستخدمين وملفاتهم الشخصية: مدير عام، رؤساء أقسام،
 * معيدون، أصحاب متاجر، وطلاب. يعتمد على وجود الأقسام والمجموعات مسبقاً
 * (AcademicSeeder)، لذلك يجب أن يُشغَّل بعده مباشرة.
 *
 * ملاحظة: UserFactory يضبط كلمة المرور الافتراضية لكل مستخدم على "password"
 * (مخزّنة بشكل ثابت static لتفادي تكرار التشفير)، لذلك كل الحسابات أدناه
 * تدخل بنفس كلمة المرور دون الحاجة لتمريرها صراحة.
 */
class UserSeeder extends Seeder
{
    private const INSTRUCTORS_COUNT = 50;
    private const STORE_OWNERS_COUNT = 10;
    private const STUDENTS_COUNT = 200;

    public function run(): void
    {
        $this->seedAdmin();
        $this->seedReceptionist();
        $this->seedDepartmentHeads();
        $this->seedInstructors();
        $this->seedStoreOwners();
        $this->seedStudents();
    }

    /**
     * موظف استقبال واحد؛ ليس ضمن الأعداد التي طلبها العميل صراحةً، لكنه
     * ضروري لأن تدفق تسجيل المرضى (PatientSeeder) يفترض وجود مستخدم بهذا الدور،
     * تماماً كما يعتمد عليه ReceptionistController فعلياً في التطبيق.
     */
    private function seedReceptionist(): void
    {
        $receptionist = User::forceCreate([
            'first_name' => 'سوزان',
            'last_name' => 'الخطيب',
            'email' => 'receptionist@dentex.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $receptionist->assignRole('receptionist');
    }

    /** حساب مدير عام واحد ثابت لتسهيل تسجيل الدخول أثناء الاختبار */
    private function seedAdmin(): void
    {
        $admin = User::forceCreate([
            'first_name' => 'مدير',
            'last_name' => 'النظام',
            'email' => 'admin@dentex.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');
    }

    /**
     * رئيس قسم واحد لكل قسم من الأقسام الخمسة المُنشأة مسبقاً (تطابق 1:1)،
     * باستخدام حالة departmentHead() في UserFactory التي تمنع تكرار تعيين
     * أكثر من رئيس لنفس القسم.
     */
    private function seedDepartmentHeads(): void
    {
        $departments = Department::all();

        foreach ($departments as $index => $department) {
            if ($index === 0) {
                // أول رئيس قسم بحساب ثابت معروف لتسهيل الاختبار اليدوي
                User::factory()->departmentHead($department)->create([
                    'first_name' => 'خالد',
                    'last_name' => 'الزين',
                    'email' => 'head@dentex.test',
                ]);

                continue;
            }

            User::factory()->departmentHead($department)->create();
        }
    }

    /**
     * 50 معيداً، ثم ربط كل معيد بـ 1 إلى 3 مجموعات عشوائية عبر جدول
     * group_instructor (هذا هو المقصود بـ "Assign Groups" في هيكلية الطلب،
     * وتوضع هنا لأن ملفات المعيدين instructor_profiles يجب أن تكون موجودة أولاً).
     */
    private function seedInstructors(): void
    {
        // معيد بحساب ثابت معروف لتسهيل الاختبار اليدوي
        User::factory()->instructor()->create([
            'first_name' => 'سارة',
            'last_name' => 'يوسف',
            'email' => 'instructor@dentex.test',
        ]);

        User::factory()->count(self::INSTRUCTORS_COUNT - 1)->instructor()->create();

        $groupIds = Group::pluck('id');

        InstructorProfile::query()->chunkById(50, function ($profiles) use ($groupIds): void {
            foreach ($profiles as $profile) {
                $randomGroupIds = $groupIds->random(min($groupIds->count(), random_int(1, 3)));
                $profile->groups()->syncWithoutDetaching($randomGroupIds);
            }
        });
    }

    private function seedStoreOwners(): void
    {
        User::factory()->asStoreOwner()->create([
            'first_name' => 'سامر',
            'last_name' => 'البني',
            'email' => 'store@dentex.test',
        ]);

        User::factory()->count(self::STORE_OWNERS_COUNT - 1)->asStoreOwner()->create();
    }

    /** 200 طالب، كل طالب يُسنَد تلقائياً لمجموعة موافقة لسنته الدراسية عبر UserFactory::student() */
    private function seedStudents(): void
    {
        User::factory()->student()->create([
            'first_name' => 'أحمد',
            'last_name' => 'خالد',
            'email' => 'student@dentex.test',
        ]);

        User::factory()->count(self::STUDENTS_COUNT - 1)->student()->create();
    }
}

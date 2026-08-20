<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Department;
use App\Models\DepartmentHeadProfile;
use App\Models\Group;
use App\Models\InstructorProfile;
use App\Models\StoreProfile;
use App\Models\StudentCourseEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * مستخدم واحد لكل دور، مع البروفايل الخاص به والربط الصحيح بالمجموعات والأقسام.
 * كل الحسابات بكلمة المرور: password
 *
 * يعتمد على AcademicSeeder (الأقسام والمجموعات والمقررات يجب أن تكون موجودة مسبقاً).
 */
class UserSeeder extends Seeder
{
    /** كلمة مرور موحّدة لكل حسابات الاختبار (يُشفّرها كاست 'hashed' في موديل User تلقائياً) */
    private const PASSWORD = 'password';

    public function run(): void
    {
        $this->seedAdmin();
        $this->seedReceptionist();
        $this->seedDepartmentHead();
        $this->seedInstructor();
        $this->seedStoreOwner();
        $this->seedRealStores();
        $this->seedStudent();
    }

    /** حساب إشرافي عام؛ الدور موجود في النظام ويملك كل الصلاحيات عبر RoleAndPermissionSeeder */
    private function seedAdmin(): void
    {
        $this->createUser('مدير', 'النظام', 'admin@test.com')->assignRole('admin');
    }

    /**
     * موظف الاستقبال: لا يملك جدول بروفايل خاص به، فدوره وحده كافٍ.
     * هو نقطة البداية في التطبيق لأنه من يُدخل المرضى الجدد (حالتهم waiting_diagnosis).
     */
    private function seedReceptionist(): void
    {
        $this->createUser('سوزان', 'الخطيب', 'receptionist@test.com')->assignRole('receptionist');
    }

    /**
     * رئيس قسم واحد لكل قسم علمي. علاقة department_head_profiles مع القسم
     * هي 1:1 (عمود department_id فريد UNIQUE)، أي لا يمكن تعيين رئيسين للقسم نفسه.
     *
     * head@test.com بقي مرتبطاً بقسم التقويم كما كان (الحساب الأصلي المستخدم
     * بالاختبار اليدوي)، وأضفنا رئيساً لكل قسم آخر لم يكن له رئيس من قبل.
     */
    private function seedDepartmentHead(): void
    {
        $heads = [
            'قسم طب الفم' => ['محمود', 'الشامي', 'head1@test.com'],
            'قسم المداواة' => ['ليلى', 'مراد', 'head2@test.com'],
            AcademicSeeder::DEPARTMENT_SURGERY => ['يوسف', 'حمدان', 'head3@test.com'],
            'قسم التعويضات المتحركة' => ['رنا', 'قاسم', 'head4@test.com'],
            AcademicSeeder::DEPARTMENT_ORTHODONTICS => ['خالد', 'الزين', 'head@test.com'],
            'قسم علم النسج حول السنية' => ['سامي', 'دبس', 'head5@test.com'],
            'قسم التعويضات الثابتة' => ['هبة', 'النابلسي', 'head6@test.com'],
            'قسم طب أسنان الأطفال' => ['نور', 'العبدالله', 'head7@test.com'],
        ];

        foreach ($heads as $departmentName => [$firstName, $lastName, $email]) {
            $department = Department::where('name', $departmentName)->firstOrFail();

            $user = $this->createUser($firstName, $lastName, $email);
            $user->assignRole('department_head');

            DepartmentHeadProfile::firstOrCreate(
                ['user_id' => $user->id],
                ['department_id' => $department->id]
            );
        }
    }

    /**
     * المعيد: نربطه بكل الفئات (كل سنوات وأقسام الطلاب) عبر جدول group_instructor.
     * هذا الربط شرط أساسي لعمل approveCase/rejectCase، لأن الخدمة ترفض بـ 403
     * أي طلب تشخيص قادم من طالب لا يشترك مع المعيد بأي فئة.
     */
    private function seedInstructor(): void
    {
        $user = $this->createUser('سارة', 'يوسف', 'instructor@test.com');
        $user->assignRole('instructor');

        $profile = InstructorProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => '0991000001',
                'specialty' => 'تقويم الأسنان',
                'specialty_year' => '2018',
            ]
        );

        $profile->groups()->syncWithoutDetaching(Group::pluck('id'));
    }

    /** صاحب المتجر: بروفايل المتجر مطلوب، ومنتجاته تُربط به عبر products.store_id (يشير إلى users.id) */
    private function seedStoreOwner(): void
    {
        $user = $this->createUser('سامر', 'البني', 'store@test.com');
        $user->assignRole('store_owner');

        StoreProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'store_name' => 'متجر دنتكس للمستلزمات السنية',
                'store_phone' => '0991000002',
                'store_address' => 'دمشق - المزة - شارع الجلاء',
            ]
        );
    }

    /**
     * متاجر/مستودعات أدوات ومواد طب أسنان حقيقية وناشطة فعلياً بدمشق (الحلبوني،
     * شارع بغداد، الميسات...)، لتعبئة سوق المتاجر ببيانات واقعية بدل بيانات وهمية.
     *
     * حساب فقط (User + StoreProfile) بلا أي منتجات أو طلبات، ليضيفها كل صاحب
     * متجر بنفسه لاحقاً عبر التطبيق.
     */
    private function seedRealStores(): void
    {
        $stores = [
            ['مركز خدمات طلاب طب الأسنان', 'دمشق - الحلبوني - بناء الخولي - الطابق الأرضي', 'store1@test.com'],
            ['RAMO MEDICAL', 'دمشق - الحلبوني', 'store2@test.com'],
            ['مستودع بقدونس لتجهيزات طب الأسنان', 'دمشق - شارع مسلم البارودي - بناء الخولي', 'store3@test.com'],
            ['مستودع التونسي لطب الأسنان', 'دمشق - الحلبوني - بناء الحلبوني', 'store4@test.com'],
            ['مستودع الروماني - مازن الروماني', 'دمشق - شارع مسلم البارودي - بناء صلاحي وخولي', 'store5@test.com'],
            ['مستودع الملكي - Royal Dent', 'دمشق - شارع بغداد - بعد محطة الأزبكية - مقابل مركز جنرال الطبي', 'store6@test.com'],
            ['الجلال لتجهيزات طب الأسنان', 'دمشق - شارع بغداد - خلف كازية عوض', 'store7@test.com'],
            ['مركز الخير للتجهيزات السنية', 'دمشق - ساحة الميسات - جانب نقابة أطباء أسنان ريف دمشق', 'store8@test.com'],
            ['مستودع الزهراء لطب الأسنان', 'دمشق - طلعة الشهندر', 'store9@test.com'],
            ['شركة نشاوي لطب الأسنان', 'دمشق', 'store10@test.com'],
            ['مستودع زيركون لطب الأسنان', 'دمشق', 'store11@test.com'],
        ];

        foreach ($stores as $index => [$storeName, $address, $email]) {
            // لا نملك اسم صاحب المتجر الشخصي الحقيقي، فنكتفي باسم المتجر التجاري
            // نفسه كاسم أول للحساب بدل اختلاق اسم عائلة وهمي لشخص غير موجود
            $user = $this->createUser($storeName, '', $email);
            $user->assignRole('store_owner');

            StoreProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $storeName,
                    'store_phone' => '099100' . str_pad((string) (10 + $index), 4, '0', STR_PAD_LEFT),
                    'store_address' => $address,
                ]
            );
        }
    }

    /**
     * الطالب: السنة الخامسة / الفصل الأول.
     *
     * السنة والفصل هنا ليسا اعتباطيين، بل مقيّدان بشرطين متعاكسين:
     *
     * 1) ميدل وير EnsureCoursesAreSetup يشترط وجود مقرر مسجَّل يطابق سنة الطالب
     *    وفصله بالضبط (= وليس <=)، وإلا رفض كل مسارات الطالب بـ 403. لذلك يجب
     *    أن يوافق (year, semester) مقرراً موجوداً فعلاً في AcademicSeeder.
     * 2) بقية التحققات (PatientService/StudentRepository) تسمح بمقررات السنوات
     *    الأدنى والفصول الأدنى (<=)، فاختيار أعلى سنة يجعل كل المقررات متاحة.
     *
     * السنة 5 / الفصل 1 تحقق الشرطين معاً: تطابق مقرري السنة الخامسة الموجودين،
     * وتتيح في الوقت نفسه الوصول لمقرري السنة الرابعة.
     */
    private function seedStudent(): void
    {
        $group = Group::where('academic_year', 5)
            ->where('group_name', AcademicSeeder::groupName(1))
            ->firstOrFail();

        $user = $this->createUser('أحمد', 'خالد', 'student@test.com');
        $user->assignRole('student');

        $profile = StudentProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'group_id' => $group->id,
                'phone' => '0991000003',
                'exam_number' => '500001',
                'university' => 'جامعة دمشق',
                'academic_year' => 5,
                'semester' => 1,
            ]
        );

        $this->enrollStudentInAllCourses($profile);
    }

    /**
     * تسجيل الطالب في كل المقررات المُنشأة: الميدل وير ensure.courses.setup يمنع
     * الوصول لمعظم مسارات الطالب ما لم يكن مسجّلاً في مقررات فعّالة، كما أن
     * الحالة التي يشخّصها المعيد لا تظهر للطالب إلا إذا كان مسجّلاً في مقررها.
     */
    private function enrollStudentInAllCourses(StudentProfile $profile): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            StudentCourseEnrollment::firstOrCreate(
                ['student_id' => $profile->id, 'course_id' => $course->id],
                ['status' => EnrollmentStatus::ACTIVE->value, 'attempts_count' => 1]
            );
        }

        // حارس ضد خطأ صامت: لو لم يوجد مقرر يطابق سنة الطالب وفصله تماماً، فسيبدو
        // الـ Seeding ناجحاً بينما يُحجب الطالب لاحقاً بـ 403 عند أول طلب.
        $matchesCurrentLevel = $courses
            ->where('year', $profile->academic_year)
            ->where('semester', $profile->semester)
            ->isNotEmpty();

        if (! $matchesCurrentLevel) {
            $this->command?->warn(sprintf(
                'تحذير: لا يوجد مقرر للسنة %d/الفصل %d، وسيرفض ميدل وير ensure.courses.setup كل مسارات الطالب.',
                $profile->academic_year,
                $profile->semester
            ));
        }
    }

    /**
     * إنشاء المستخدم مع تفعيل بريده مسبقاً حتى لا يعترض تدفق التحقق (OTP) الاختبار.
     * نستخدم firstOrCreate على البريد ليبقى السيدر قابلاً لإعادة التشغيل دون تكرار.
     */
    private function createUser(string $firstName, string $lastName, string $email): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => self::PASSWORD,
            ]
        );

        // email_verified_at خارج $fillable في موديل User، فلا يمكن تمريره ضمن create
        // (سيُهمل بصمت). ونضبطه إلزامياً لأن AuthService يرفض تسجيل الدخول لأي
        // حساب غير مُفعّل، بل ويحذفه CleanUnverifiedUsersCommand لاحقاً.
        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }
}

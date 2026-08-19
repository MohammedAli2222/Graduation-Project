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
     * رئيس القسم: مرتبط بقسم التقويم. علاقة department_head_profiles مع القسم
     * هي 1:1 (عمود department_id فريد UNIQUE)، أي لا يمكن تعيين رئيسين للقسم نفسه.
     */
    private function seedDepartmentHead(): void
    {
        $department = Department::where('name', AcademicSeeder::DEPARTMENT_ORTHODONTICS)->firstOrFail();

        $user = $this->createUser('خالد', 'الزين', 'head@test.com');
        $user->assignRole('department_head');

        DepartmentHeadProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['department_id' => $department->id]
        );
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

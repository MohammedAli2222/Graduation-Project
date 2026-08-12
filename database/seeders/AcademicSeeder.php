<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseType;
use App\Models\Course;
use App\Models\Department;
use App\Models\Group;
use Illuminate\Database\Seeder;

/**
 * الهيكل الأكاديمي الأساسي: قسمان، مجموعتان، أربعة مقررات، وخمس حالات سريرية.
 *
 * يجب أن يُشغَّل قبل UserSeeder لأن student_profiles.group_id مفتاح خارجي
 * إلزامي (NOT NULL) نحو جدول groups، أي لا يمكن إنشاء طالب قبل وجود المجموعات.
 *
 * ملاحظة: أسماء الأقسام/المجموعات/المقررات مستخدمة كمفاتيح بحث في UserSeeder،
 * لذلك أي تعديل عليها هنا يجب أن يقابله تعديل هناك.
 */
class AcademicSeeder extends Seeder
{
    /** أسماء ثابتة نشير إليها من السيدرات الأخرى بدل الاعتماد على ترتيب الـ id */
    public const DEPARTMENT_ORTHODONTICS = 'قسم تقويم الأسنان والفكين';
    public const DEPARTMENT_SURGERY = 'قسم جراحة الفم والفكين';

    public const GROUP_YEAR_FOUR = 'المجموعة الأولى - السنة الرابعة';
    public const GROUP_YEAR_FIVE = 'المجموعة الأولى - السنة الخامسة';

    public function run(): void
    {
        $departments = $this->seedDepartments();
        $this->seedGroups();
        $courses = $this->seedCourses($departments);
        $this->seedCaseTypes($courses);
    }

    /**
     * الأقسام: كل قسم يملك عدد كراسي (وحدات العلاج) الخاص به، وهو ما تعتمد عليه
     * حسابات الطاقة الاستيعابية عند حجز المواعيد.
     *
     * @return array<string, Department>
     */
    private function seedDepartments(): array
    {
        $orthodontics = Department::firstOrCreate(
            ['name' => self::DEPARTMENT_ORTHODONTICS],
            [
                'total_chairs' => 8,
                'description' => 'يعنى بتقويم الأسنان وتصحيح سوء الإطباق والتشوهات الفكية.',
            ]
        );

        $surgery = Department::firstOrCreate(
            ['name' => self::DEPARTMENT_SURGERY],
            [
                'total_chairs' => 10,
                'description' => 'يعنى بالتخدير الموضعي وقلع الأسنان والجراحة الفموية.',
            ]
        );

        return [
            'orthodontics' => $orthodontics,
            'surgery' => $surgery,
        ];
    }

    /**
     * المجموعات: مجموعة لكل سنة سريرية. عمود academic_year هو ما يربط الطالب
     * بمجموعة موافقة لسنته، ويحدد أي معيد يشرف عليه (عبر جدول group_instructor).
     */
    private function seedGroups(): void
    {
        Group::firstOrCreate(
            ['group_name' => self::GROUP_YEAR_FOUR],
            ['academic_year' => 4]
        );

        Group::firstOrCreate(
            ['group_name' => self::GROUP_YEAR_FIVE],
            ['academic_year' => 5]
        );
    }

    /**
     * المقررات: مقرران لكل قسم، موزّعان على السنتين الرابعة والخامسة.
     * الحقلان year و semester يحكمان صلاحية الطالب للوصول لحالات المقرر
     * (الطالب يصل لمقررات سنته الحالية والسنوات الأدنى منها).
     *
     * @param  array<string, Department>  $departments
     * @return array<string, Course>
     */
    private function seedCourses(array $departments): array
    {
        $orthodonticsOne = Course::firstOrCreate(
            ['name' => 'تقويم الأسنان 1', 'department_id' => $departments['orthodontics']->id],
            ['year' => 4, 'semester' => 1]
        );

        $orthodonticsTwo = Course::firstOrCreate(
            ['name' => 'تقويم الأسنان 2', 'department_id' => $departments['orthodontics']->id],
            ['year' => 5, 'semester' => 1]
        );

        $surgeryOne = Course::firstOrCreate(
            ['name' => 'جراحة الفم والفكين 1', 'department_id' => $departments['surgery']->id],
            ['year' => 4, 'semester' => 1]
        );

        $surgeryTwo = Course::firstOrCreate(
            ['name' => 'جراحة الفم والفكين 2', 'department_id' => $departments['surgery']->id],
            ['year' => 5, 'semester' => 1]
        );

        return [
            'orthodontics_1' => $orthodonticsOne,
            'orthodontics_2' => $orthodonticsTwo,
            'surgery_1' => $surgeryOne,
            'surgery_2' => $surgeryTwo,
        ];
    }

    /**
     * الحالات السريرية (Case Types) وهي جوهر التطبيق: المعيد يشخّص المريض بحالة
     * منها، والطالب يحجزها لإتمام متطلبات مقرره.
     *
     * - required_count: عدد المرات التي يجب على الطالب إنجاز هذه الحالة فيها لينجح بالمقرر.
     * - slots_needed:  عدد الجلسات (المواعيد) التي تحتاجها الحالة الواحدة حتى تكتمل.
     *
     * @param  array<string, Course>  $courses
     */
    private function seedCaseTypes(array $courses): void
    {
        $caseTypes = [
            [
                'name' => 'تقويم ثابت',
                'course_id' => $courses['orthodontics_2']->id,
                'required_count' => 2,
                'slots_needed' => 4,
            ],
            [
                'name' => 'جهاز تقويم متحرك',
                'course_id' => $courses['orthodontics_1']->id,
                'required_count' => 1,
                'slots_needed' => 2,
            ],
            [
                'name' => 'قلع سن دائم',
                'course_id' => $courses['surgery_1']->id,
                'required_count' => 3,
                'slots_needed' => 1,
            ],
            [
                'name' => 'خياطة جرح فموي',
                'course_id' => $courses['surgery_1']->id,
                'required_count' => 2,
                'slots_needed' => 1,
            ],
            [
                'name' => 'قلع جراحي لرحى منطمرة',
                'course_id' => $courses['surgery_2']->id,
                'required_count' => 1,
                'slots_needed' => 2,
            ],
        ];

        foreach ($caseTypes as $caseType) {
            CaseType::firstOrCreate(
                ['name' => $caseType['name'], 'course_id' => $caseType['course_id']],
                [
                    'required_count' => $caseType['required_count'],
                    'slots_needed' => $caseType['slots_needed'],
                ]
            );
        }
    }
}

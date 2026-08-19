<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseType;
use App\Models\Course;
use App\Models\Department;
use App\Models\Group;
use Illuminate\Database\Seeder;

/**
 * الهيكل الأكاديمي الأساسي: أقسام ومقررات كلية طب الأسنان بجامعة دمشق
 * (السنتان السريريتان الرابعة والخامسة)، إضافة إلى فئات (مجموعات) كل سنة.
 *
 * يجب أن يُشغَّل قبل UserSeeder لأن student_profiles.group_id مفتاح خارجي
 * إلزامي (NOT NULL) نحو جدول groups، أي لا يمكن إنشاء طالب قبل وجود المجموعات.
 *
 * ملاحظة: أسماء الأقسام/المجموعات مستخدمة كمفاتيح بحث في UserSeeder،
 * لذلك أي تعديل عليها هنا يجب أن يقابله تعديل هناك.
 */
class AcademicSeeder extends Seeder
{
    /** أسماء ثابتة نشير إليها من السيدرات الأخرى بدل الاعتماد على ترتيب الـ id */
    public const DEPARTMENT_ORTHODONTICS = 'قسم تقويم الأسنان والفكين';
    public const DEPARTMENT_SURGERY = 'قسم جراحة الفم والفكين';

    /** السنوات السريرية التي تُنشأ لها فئات، وعدد الفئات لكل سنة منها */
    private const CLINICAL_YEARS = [4, 5];
    public const GROUPS_PER_YEAR = 5;
    private const GROUP_NAME_PREFIX = 'الفئة ';

    /** اسم الفئة رقم $index (1-based) ضمن سنتها؛ تستخدمها السيدرات الأخرى للبحث عن فئة محددة */
    public static function groupName(int $index): string
    {
        return self::GROUP_NAME_PREFIX.$index;
    }

    public function run(): void
    {
        $departments = $this->seedDepartments();
        $this->seedGroups();
        $courses = $this->seedCourses($departments);
        $this->seedCaseTypes($courses);
    }

    /**
     * الأقسام العلمية في كلية طب الأسنان، مستخرجة من الخطة الدراسية الرسمية.
     *
     * @return array<string, Department>
     */
    private function seedDepartments(): array
    {
        $departmentNames = [
            'قسم طب الفم',
            'قسم المداواة',
            self::DEPARTMENT_SURGERY,
            'قسم التعويضات المتحركة',
            self::DEPARTMENT_ORTHODONTICS,
            'قسم علم النسج حول السنية',
            'قسم التعويضات الثابتة',
            'قسم طب أسنان الأطفال',
        ];

        $departments = [];

        foreach ($departmentNames as $name) {
            $departments[$name] = Department::create([
                'name' => $name,
                'total_chairs' => 10,
            ]);
        }

        return $departments;
    }

    /**
     * الفئات (المجموعات): GROUPS_PER_YEAR فئة لكل سنة سريرية، بأسماء متكررة عبر
     * السنوات (الفئة 1، الفئة 2...) والتمييز الفعلي بينها عبر عمود academic_year،
     * وهو ما يربط الطالب بفئة موافقة لسنته ويحدد أي معيد يشرف عليه (عبر group_instructor).
     */
    private function seedGroups(): void
    {
        foreach (self::CLINICAL_YEARS as $year) {
            for ($i = 1; $i <= self::GROUPS_PER_YEAR; $i++) {
                Group::firstOrCreate([
                    'group_name' => self::groupName($i),
                    'academic_year' => $year,
                ]);
            }
        }
    }

    /**
     * الخطة الدراسية الرسمية لكلية طب الأسنان - جامعة دمشق (السنتان الرابعة والخامسة).
     *
     * @return array<int, array<int, array<int, array{name: string, department: string}>>>
     */
    private function curriculum(): array
    {
        return [
            4 => [
                1 => [
                    ['name' => 'أمراض الفم (1)', 'department' => 'قسم طب الفم'],
                    ['name' => 'مداواة الأسنان اللبية (1)', 'department' => 'قسم المداواة'],
                    ['name' => 'التخدير والقلع (1)', 'department' => self::DEPARTMENT_SURGERY],
                    ['name' => 'تعويضات الأسنان المتحركة (2)', 'department' => 'قسم التعويضات المتحركة'],
                    ['name' => 'تقويم الأسنان والفكين (1)', 'department' => self::DEPARTMENT_ORTHODONTICS],
                    ['name' => 'أمراض النسج حول السنية (2)', 'department' => 'قسم علم النسج حول السنية'],
                    ['name' => 'مداواة الأسنان المحافظة (3)', 'department' => 'قسم المداواة'],
                ],
                2 => [
                    ['name' => 'أمراض الفم (2)', 'department' => 'قسم طب الفم'],
                    ['name' => 'مداواة الأسنان اللبية (2)', 'department' => 'قسم المداواة'],
                    ['name' => 'التخدير والقلع (2)', 'department' => self::DEPARTMENT_SURGERY],
                    ['name' => 'تعويضات الأسنان المتحركة (3)', 'department' => 'قسم التعويضات المتحركة'],
                    ['name' => 'تقويم الأسنان والفكين (2)', 'department' => self::DEPARTMENT_ORTHODONTICS],
                    ['name' => 'تعويضات الأسنان الثابتة (3)', 'department' => 'قسم التعويضات الثابتة'],
                    ['name' => 'طب أسنان الأطفال (1)', 'department' => 'قسم طب أسنان الأطفال'],
                ],
            ],
            5 => [
                1 => [
                    ['name' => 'التخدير والقلع (3)', 'department' => self::DEPARTMENT_SURGERY],
                    ['name' => 'مداواة الأسنان اللبية (3)', 'department' => 'قسم المداواة'],
                    ['name' => 'تعويضات الأسنان الثابتة (4)', 'department' => 'قسم التعويضات الثابتة'],
                    ['name' => 'تعويضات الأسنان المتحركة (4)', 'department' => 'قسم التعويضات المتحركة'],
                    ['name' => 'طب أسنان الأطفال (2)', 'department' => 'قسم طب أسنان الأطفال'],
                    ['name' => 'أمراض النسج حول السنية (3)', 'department' => 'قسم علم النسج حول السنية'],
                    ['name' => 'الجراحة الفموية وزرع الأسنان', 'department' => self::DEPARTMENT_SURGERY],
                ],
                2 => [
                    ['name' => 'التخدير والقلع (4)', 'department' => self::DEPARTMENT_SURGERY],
                    ['name' => 'مداواة الأسنان اللبية (4)', 'department' => 'قسم المداواة'],
                    ['name' => 'تعويضات الأسنان الثابتة (5)', 'department' => 'قسم التعويضات الثابتة'],
                    ['name' => 'مداواة الأسنان المحافظة (4)', 'department' => 'قسم المداواة'],
                    ['name' => 'تقويم الأسنان والفكين (3)', 'department' => self::DEPARTMENT_ORTHODONTICS],
                    ['name' => 'الجراحة الفكية الوجهية', 'department' => self::DEPARTMENT_SURGERY],
                ],
            ],
        ];
    }

    private function seedCourses(array $departments): array
    {
        $courses = [];

        foreach ($this->curriculum() as $year => $semesters) {
            foreach ($semesters as $semester => $courseList) {
                foreach ($courseList as $courseData) {
                    $courses[] = Course::create([
                        'name' => $courseData['name'],
                        'department_id' => $departments[$courseData['department']]->id,
                        'year' => $year,
                        'semester' => $semester,
                    ]);
                }
            }
        }

        return $courses;
    }

    /**
     * حالة سريرية واحدة لكل مقرر، وذلك حتى لا يتعطل نظام حجز الحالات السريرية
     * الذي يعتمد على وجود case_types مرتبطة بالمقررات.
     *
     * @param  array<int, Course>  $courses
     */
    private function seedCaseTypes(array $courses): void
    {
        foreach ($courses as $course) {
            CaseType::create([
                'name' => 'حالة عملية - '.$course->name,
                'course_id' => $course->id,
                'required_count' => 2,
                'slots_needed' => 1,
            ]);
        }
    }
}

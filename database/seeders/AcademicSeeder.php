<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaseType;
use App\Models\Course;
use App\Models\Department;
use App\Models\Group;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * يبني الهيكل الأكاديمي الحقيقي لكلية طب الأسنان بجامعة دمشق (السنتان
 * السريريتان الرابعة والخامسة): 8 أقسام فعلية، و28 مقرراً سريرياً بأسمائها
 * وسنتها وفصلها وقسمها الحقيقي بالضبط كما وردت من العميل، و50 حالة سريرية
 * (Case Types) مُسندة يدوياً لكل مقرر بما يطابق موضوعه الطبي فعلياً — لا يوجد
 * أي استخدام لـ Faker أو اختيار عشوائي لأسماء الأقسام/المقررات/الحالات هنا،
 * ولا أي سحب من مجموعة مشتركة قد تربط حالة بقسم لا علاقة له بها.
 *
 * ملاحظة على الترتيب: نُشغّل هذا الـ Seeder قبل UserSeeder لأن عمود
 * student_profiles.group_id مفتاح خارجي إلزامي (NOT NULL) نحو جدول groups.
 */
class AcademicSeeder extends Seeder
{
    /**
     * الأقسام الثمانية الفعلية في الكلية، بمعلوماتها الوصفية.
     *
     * @var array<string, array{total_chairs: int, description: string}>
     */
    private const DEPARTMENTS = [
        'قسم طب الفم' => [
            'total_chairs' => 6,
            'description' => 'يختص بتشخيص أمراض الفم والغشاء المخاطي، والطوارئ السنية، والحالات الجهازية المرتبطة بصحة الفم.',
        ],
        'قسم جراحة الفم والفكين' => [
            'total_chairs' => 8,
            'description' => 'يعنى بالتخدير الموضعي، قلع الأسنان، الجراحة الفموية، زراعة الأسنان، والجراحة الفكية الوجهية.',
        ],
        'قسم تقويم الأسنان والفكين' => [
            'total_chairs' => 8,
            'description' => 'يعنى بتقويم الأسنان وتصحيح سوء الإطباق والتشوهات الفكية لدى الأطفال والبالغين.',
        ],
        'قسم تعويضات الأسنان الثابتة' => [
            'total_chairs' => 10,
            'description' => 'يختص بتعويض الأسنان المفقودة أو التالفة عبر التيجان والجسور الثابتة.',
        ],
        'قسم علم النسج حول السنية' => [
            'total_chairs' => 10,
            'description' => 'مختص بتشخيص ومعالجة أمراض اللثة والنسج الداعمة للسن، جراحياً وغير جراحياً.',
        ],
        'قسم طب أسنان الأطفال' => [
            'total_chairs' => 6,
            'description' => 'مختص بمعالجة أسنان الأطفال، والإجراءات الوقائية، وتدبير حالات الطوارئ وذوي الاحتياجات الخاصة.',
        ],
        'قسم المداواة' => [
            'total_chairs' => 12,
            'description' => 'يعنى بالمداواة السنية المحافظة (الحشوات التجميلية) ومعالجة الأسنان اللبية (علاج العصب).',
        ],
        'قسم تعويضات الأسنان المتحركة' => [
            'total_chairs' => 8,
            'description' => 'يختص بالأطقم الجزئية والكاملة المتحركة لتعويض الأسنان المفقودة.',
        ],
    ];

    /**
     * المنهاج السريري الكامل كما ورد من العميل بالضبط: السنة -> الفصل -> قائمة
     * المقررات، وكل مقرر يحمل اسمه الحقيقي، قسمه الحقيقي، وحالاته السريرية
     * الخاصة به (مُسنَدة يدوياً حسب موضوع المقرر ومستواه، وليست عشوائية).
     *
     * @var array<int, array<int, array<int, array{name: string, department: string, case_types: array<int, string>}>>>
     */
    private const CLINICAL_YEARS = [
        4 => [
            1 => [
                [
                    'name' => 'أمراض الفم (1)',
                    'department' => 'قسم طب الفم',
                    'case_types' => ['تشخيص آفة فموية وأخذ خزعة تشخيصية'],
                ],
                [
                    'name' => 'مداواة الأسنان اللبية (1)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['علاج عصب أمامي وحيد الجذر', 'علاج عصب ضاحك أحادي القناة', 'كشف وتنظيف أقنية جذرية أولي'],
                ],
                [
                    'name' => 'التخدير والقلع (1)',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['حصار عصبي وتخدير موضعي', 'قلع سن أمامي وحيد الجذر'],
                ],
                [
                    'name' => 'تعويضات الأسنان المتحركة (2)',
                    'department' => 'قسم تعويضات الأسنان المتحركة',
                    'case_types' => ['طقم أسنان جزئي متحرك', 'تعديل وإصلاح طقم أسنان'],
                ],
                [
                    'name' => 'تقويم الأسنان والفكين (1)',
                    'department' => 'قسم تقويم الأسنان والفكين',
                    'case_types' => ['تركيب جهاز تقويم متحرك', 'متابعة وتفعيل جهاز تقويمي'],
                ],
                [
                    'name' => 'أمراض النسج حول السنية (2)',
                    'department' => 'قسم علم النسج حول السنية',
                    'case_types' => ['إزالة جير فوقي وتحت لثوي', 'تقليح وتسوية جذور كامل الفكين'],
                ],
                [
                    'name' => 'مداواة الأسنان المحافظة (3)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['حشوة كومبوزيت (سطح واحد)', 'حشوة عنقية Class V'],
                ],
            ],
            2 => [
                [
                    'name' => 'أمراض الفم (2)',
                    'department' => 'قسم طب الفم',
                    'case_types' => ['متابعة علاجية لحالة فموية مزمنة'],
                ],
                [
                    'name' => 'مداواة الأسنان اللبية (2)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['علاج عصب ضاحك ثنائي القنوات', 'علاج عصب ضرس خلفي متعدد الأقنية'],
                ],
                [
                    'name' => 'التخدير والقلع (2)',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['قلع ضرس متعدد الجذور', 'قلع جذر متبقٍ'],
                ],
                [
                    'name' => 'تعويضات الأسنان المتحركة (3)',
                    'department' => 'قسم تعويضات الأسنان المتحركة',
                    'case_types' => ['طقم أسنان كامل (علوي وسفلي)'],
                ],
                [
                    'name' => 'تقويم الأسنان والفكين (2)',
                    'department' => 'قسم تقويم الأسنان والفكين',
                    'case_types' => ['تركيب جهاز تقويم ثابت جزئي'],
                ],
                [
                    'name' => 'تعويضات الأسنان الثابتة (3)',
                    'department' => 'قسم تعويضات الأسنان الثابتة',
                    'case_types' => ['تحضير تاج معدني خزفي PFM', 'تحضير جسر ثلاثي الوحدات'],
                ],
                [
                    'name' => 'طب أسنان الأطفال (1)',
                    'department' => 'قسم طب أسنان الأطفال',
                    'case_types' => ['حشوة لسن لبني', 'قلع سن لبني'],
                ],
                [
                    'name' => 'طب الطوارئ وتدبير مرضى الاحتياجات الخاصة في طب الأسنان',
                    'department' => 'قسم طب أسنان الأطفال',
                    'case_types' => ['معالجة إسعافية لألم سني حاد', 'تثبيت سن مخلوع رضياً (إعادة زرع)'],
                ],
            ],
        ],
        5 => [
            1 => [
                [
                    'name' => 'التخدير والقلع (3)',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['قلع جراحي لسن مطمور', 'قلع جراحي لضرس عقل مطمور جزئياً'],
                ],
                [
                    'name' => 'مداواة الأسنان اللبية (3)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['إعادة معالجة لبية فاشلة', 'علاج لب مباشر لتعرض لبي'],
                ],
                [
                    'name' => 'تعويضات الأسنان الثابتة (4)',
                    'department' => 'قسم تعويضات الأسنان الثابتة',
                    'case_types' => ['تاج خزفي كامل (All-Ceramic)', 'جسر خزفي متعدد الوحدات'],
                ],
                [
                    'name' => 'تعويضات الأسنان المتحركة (4)',
                    'department' => 'قسم تعويضات الأسنان المتحركة',
                    'case_types' => ['طقم أسنان فوري'],
                ],
                [
                    'name' => 'طب أسنان الأطفال (2)',
                    'department' => 'قسم طب أسنان الأطفال',
                    'case_types' => ['تاج فولاذي جاهز لسن لبني', 'علاج عصب جذري لسن لبني'],
                ],
                [
                    'name' => 'أمراض النسج حول السنية (3)',
                    'department' => 'قسم علم النسج حول السنية',
                    'case_types' => ['جراحة لثوية تصحيحية', 'تطعيم لثوي لمعالجة الانحسار'],
                ],
                [
                    'name' => 'الجراحة الفموية وزرع الأسنان',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['زراعة سن مفرد', 'استئصال جراحي لكيس فكي'],
                ],
            ],
            2 => [
                [
                    'name' => 'التخدير والقلع (4)',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['قلع جراحي معقد وتعديل عظمي', 'استئصال ورم حميد في الفك'],
                ],
                [
                    'name' => 'مداواة الأسنان اللبية (4)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['جراحة قمة الجذر (Apicoectomy)'],
                ],
                [
                    'name' => 'تعويضات الأسنان الثابتة (5)',
                    'department' => 'قسم تعويضات الأسنان الثابتة',
                    'case_types' => ['ترميم بقشرة خزفية (Veneer)', 'دعامة وإعادة بناء تاج بعد علاج عصب (Post & Core)'],
                ],
                [
                    'name' => 'مداواة الأسنان المحافظة (4)',
                    'department' => 'قسم المداواة',
                    'case_types' => ['حشوة كومبوزيت (سطحين أو أكثر)', 'ترميم تجميلي متقدم للأسنان الأمامية'],
                ],
                [
                    'name' => 'تقويم الأسنان والفكين (3)',
                    'department' => 'قسم تقويم الأسنان والفكين',
                    'case_types' => ['معالجة تقويمية شاملة لحالة هيكلية', 'تركيب وتفعيل جهاز توسيع الحنك'],
                ],
                [
                    'name' => 'الجراحة الفكية الوجهية',
                    'department' => 'قسم جراحة الفم والفكين',
                    'case_types' => ['استئصال آفة فموية جراحياً'],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->seedGroups();
        $this->seedDepartments();
        $this->seedCoursesAndCaseTypes();
    }

    /**
     * 8 مجموعات: 4 لكل من السنة الرابعة والخامسة، تماماً كما تفترض
     * واجهة التسجيل (GroupController::getGroupsByYear يقبل فقط 4 أو 5).
     */
    private function seedGroups(): void
    {
        $letters = ['أ', 'ب', 'ج', 'د'];

        foreach ([4, 5] as $academicYear) {
            foreach ($letters as $letter) {
                Group::firstOrCreate([
                    'group_name' => "المجموعة {$letter} - " . ($academicYear === 4 ? 'السنة الرابعة' : 'السنة الخامسة'),
                    'academic_year' => $academicYear,
                ]);
            }
        }
    }

    private function seedDepartments(): void
    {
        foreach (self::DEPARTMENTS as $name => $meta) {
            Department::firstOrCreate(['name' => $name], $meta);
        }

        $this->command?->info('  → 8 أقسام سريرية حقيقية.');
    }

    /**
     * ينشئ المقررات الثمانية والعشرين وحالاتها السريرية الخمسين، معتمداً على
     * الأسماء الحقيقية للأقسام التي أُنشئت للتو في seedDepartments().
     */
    private function seedCoursesAndCaseTypes(): void
    {
        $departmentIds = Department::pluck('id', 'name');

        $coursesCreated = 0;
        $caseTypesCreated = 0;

        foreach (self::CLINICAL_YEARS as $year => $semesters) {
            foreach ($semesters as $semester => $courses) {
                foreach ($courses as $courseData) {
                    $departmentId = $departmentIds[$courseData['department']] ?? null;

                    if ($departmentId === null) {
                        throw new RuntimeException(
                            "القسم \"{$courseData['department']}\" غير موجود ضمن DEPARTMENTS — تحقق من تطابق الأسماء بالضبط."
                        );
                    }

                    $course = Course::firstOrCreate(
                        ['name' => $courseData['name']],
                        ['department_id' => $departmentId, 'year' => $year, 'semester' => $semester]
                    );
                    $coursesCreated++;

                    foreach ($courseData['case_types'] as $caseTypeName) {
                        CaseType::create([
                            'course_id' => $course->id,
                            'name' => $caseTypeName,
                            'required_count' => random_int(2, 6),
                            'slots_needed' => random_int(1, 2),
                        ]);
                        $caseTypesCreated++;
                    }
                }
            }
        }

        $this->command?->info("  → {$coursesCreated} مقرراً سريرياً حقيقياً (السنة 4 والسنة 5).");
        $this->command?->info("  → {$caseTypesCreated} حالة سريرية مرتبطة موضوعياً بمقرراتها الصحيحة.");
    }
}

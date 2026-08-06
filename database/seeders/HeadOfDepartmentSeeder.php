<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\PatientStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\CaseType;
use App\Models\Department;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\StudentCourseEnrollment;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * يُنشئ بيانات تجريبية غنية لاختبار لوحة تحكم "رئيس القسم" (department_head):
 * رئيس قسم واحد مرتبط فعلياً بقسم حقيقي (غير المُتأثر بخطأ عدم تطابق أسماء
 * الأقسام في UserSeeder)، معيدون، طلاب مسجّلون بمقررات القسم، أنواع حالات
 * سريرية، ومرضى/تشخيصات/معالجات/مواعيد بحالات متنوعة تكفي لاختبار الترقيم
 * (Pagination)، الفلاتر، والإحصائيات على مسارات /api/hod/* الفعلية.
 *
 * الاعتماديات: RoleSeeder و DepartmentAndCourseSeeder (أو تشغيل php artisan db:seed
 * الكامل) يجب أن يكونا قد نُفِّذا قبل هذا الـ Seeder.
 *
 * التشغيل: php artisan db:seed --class="Database\Seeders\HeadOfDepartmentSeeder"
 */
class HeadOfDepartmentSeeder extends Seeder
{
    // "قسم المداواة" هو القسم الأغنى بالمقررات (6 مقررات)، وهو غير مُسنَد لأي
    // رئيس قسم حالياً بسبب خطأ تطابق الأسماء في UserSeeder ('قسم المداواة والترميم'
    // لا يطابق الاسم الحقيقي 'قسم المداواة') — لذا هو هدف آمن لا يصطدم بالقيد الفريد.
    private const TARGET_DEPARTMENT_NAME = 'قسم المداواة';

    private const HEAD_EMAIL = 'head.demo@dentsyria.edu.sy';
    private const INSTRUCTOR_COUNT = 5;
    private const STUDENT_COUNT = 25;
    private const PATIENT_COUNT = 50;

    // الحد الأدنى المضمون من المعالجات "المكتملة" لضمان تجاوز صفحة الـ 15 عنصر
    // الافتراضية في /hod/completed-treatments (اختبار حقيقي للـ Pagination).
    private const GUARANTEED_COMPLETED_TREATMENTS = 20;

    private const CASE_TYPE_NAMES = [
        'حشوة كومبوزيت (سطح واحد)',
        'حشوة كومبوزيت (سطحين أو أكثر)',
        'حشوة عنقية Class V',
        'حشوة أملغم خلفية',
        'علاج عصب أمامي وحيد الجذر',
        'علاج عصب ضاحك أحادي القناة',
        'علاج عصب ضاحك ثنائي القنوات',
        'علاج عصب ضرس خلفي متعدد الأقنية',
        'إعادة معالجة لبية فاشلة',
        'جراحة قمة الجذر (Apicoectomy)',
    ];

    public function run(): void
    {
        Role::firstOrCreate(['name' => 'department_head']);
        Role::firstOrCreate(['name' => 'instructor']);
        Role::firstOrCreate(['name' => 'student']);
        Role::firstOrCreate(['name' => 'receptionist']);

        $department = Department::where('name', self::TARGET_DEPARTMENT_NAME)->first();
        if (! $department) {
            throw new RuntimeException(
                'القسم "' . self::TARGET_DEPARTMENT_NAME . '" غير موجود. شغّل DepartmentAndCourseSeeder '
                . '(أو php artisan db:seed الكامل) قبل تشغيل هذا الـ Seeder.'
            );
        }

        $courses = $department->courses;
        if ($courses->isEmpty()) {
            throw new RuntimeException('القسم المستهدف لا يملك أي مقررات بعد. شغّل DepartmentAndCourseSeeder أولاً.');
        }

        $headUser = $this->resolveHead($department);
        $instructors = $this->resolveInstructors();
        $students = $this->resolveStudents();
        $receptionist = $this->resolveReceptionist();

        $this->enrollStudentsInCourses($students, $courses);
        $caseTypes = $this->seedCaseTypes($courses);

        $this->seedClinicalData($department, $caseTypes, $students, $instructors, $receptionist);

        $this->command?->info("تم إنشاء بيانات رئيس القسم بنجاح — القسم: {$department->name}");
        $this->command?->info('بيانات دخول رئيس القسم: ' . self::HEAD_EMAIL . ' / password');
    }

    private function resolveHead(Department $department): User
    {
        $head = User::where('email', self::HEAD_EMAIL)->first();

        if (! $head) {
            $head = User::factory()->departmentHead($department)->create([
                'first_name' => 'خالد',
                'last_name' => 'الزين',
                'email' => self::HEAD_EMAIL,
            ]);
        } elseif (! $head->departmentHeadProfile) {
            $head->assignRole('department_head');
            $head->departmentHeadProfile()->create(['department_id' => $department->id]);
        }

        return $head;
    }

    /** @return Collection<int, User> */
    private function resolveInstructors(): Collection
    {
        return collect(range(1, self::INSTRUCTOR_COUNT))->map(function (int $i) {
            $email = "demo.instructor{$i}@dentsyria.edu.sy";
            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::factory()->instructor()->create(['email' => $email]);
            } elseif (! $user->instructorProfile) {
                $user->assignRole('instructor');
                $user->instructorProfile()->create([
                    'phone' => '09' . rand(3, 9) . rand(1000000, 9999999),
                    'specialty' => 'اختصاصي مداواة ترميمية',
                    'specialty_year' => (string) rand(2012, 2022),
                ]);
            }

            return $user;
        });
    }

    /** @return Collection<int, User> */
    private function resolveStudents(): Collection
    {
        return collect(range(1, self::STUDENT_COUNT))->map(
            fn (int $i) => User::where('email', "demo.student{$i}@dentsyria.edu.sy")->first()
                ?? User::factory()->student()->create(['email' => "demo.student{$i}@dentsyria.edu.sy"])
        );
    }

    private function resolveReceptionist(): User
    {
        $email = 'demo.receptionist@dentsyria.edu.sy';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::factory()->create(['email' => $email]);
            $user->assignRole('receptionist');
        }

        return $user;
    }

    private function enrollStudentsInCourses(Collection $students, Collection $courses): void
    {
        foreach ($courses as $course) {
            $enrollCount = min($students->count(), rand(10, 18));

            foreach ($students->random($enrollCount) as $student) {
                $profile = $student->studentProfile;
                if (! $profile) {
                    continue;
                }

                StudentCourseEnrollment::firstOrCreate(
                    ['student_id' => $profile->id, 'course_id' => $course->id],
                    ['status' => $this->weightedEnrollmentStatus(), 'attempts_count' => rand(1, 2)]
                );
            }
        }
    }

    /** @return Collection<int, CaseType> */
    private function seedCaseTypes(Collection $courses): Collection
    {
        foreach ($courses as $course) {
            if ($course->caseTypes()->exists()) {
                continue;
            }

            $names = collect(self::CASE_TYPE_NAMES)->shuffle()->take(rand(2, 4));

            foreach ($names as $name) {
                CaseType::factory()->for($course)->create([
                    'name' => $name,
                    'required_count' => rand(2, 6),
                    'slots_needed' => rand(1, 2),
                ]);
            }
        }

        return CaseType::whereIn('course_id', $courses->pluck('id'))->get();
    }

    private function seedClinicalData(
        Department $department,
        Collection $caseTypes,
        Collection $students,
        Collection $instructors,
        User $receptionist
    ): void {
        $statusPool = $this->buildDiagnosisStatusPool(self::PATIENT_COUNT);
        $treatmentIndex = 0;

        for ($i = 0; $i < self::PATIENT_COUNT; $i++) {
            $patient = Patient::factory()->create(['added_by' => $receptionist->id]);

            $status = $statusPool[$i];
            $isTreatmentEligible = in_array($status, [
                DiagnosisStatus::CONVERTED_TO_TREATMENT->value,
                DiagnosisStatus::FINISHED->value,
            ], true);
            $needsInstructor = $isTreatmentEligible || $status === DiagnosisStatus::RESERVED->value
                || $status === DiagnosisStatus::REJECTED->value;

            $diagnosis = PatientDiagnose::factory()
                ->state(fn () => [
                    'status' => $status,
                    'rejection_reason' => $status === DiagnosisStatus::REJECTED->value
                        ? 'الحالة معقدة جداً ولا تتناسب مع المستوى الأكاديمي للطالب.'
                        : null,
                ])
                ->create([
                    'patient_id' => $patient->id,
                    'case_type_id' => $caseTypes->random()->id,
                    'department_id' => $department->id,
                    'suggested_by_student_id' => $students->random()->id,
                    'instructor_id' => $needsInstructor ? $instructors->random()->id : null,
                ]);

            $patientStatus = match (true) {
                $isTreatmentEligible => PatientStatus::FULLY_RESERVED->value,
                $status === DiagnosisStatus::AVAILABLE->value => PatientStatus::AVAILABLE->value,
                default => null,
            };

            if ($isTreatmentEligible) {
                $treatmentIndex++;
                $treatmentStatus = $this->pickTreatmentStatus($treatmentIndex);

                $treatment = Treatment::factory()
                    ->state(function (array $attrs) use ($treatmentStatus) {
                        $startDate = $attrs['start_date'] instanceof \DateTimeInterface
                            ? Carbon::instance($attrs['start_date'])
                            : Carbon::parse($attrs['start_date']);

                        return [
                            'status' => $treatmentStatus,
                            'end_date' => $treatmentStatus === TreatmentStatus::COMPLETED->value
                                ? $startDate->copy()->addDays(rand(1, 14))
                                : null,
                            'rejection_reason' => $treatmentStatus === TreatmentStatus::REJECTED->value
                                ? 'لا تتوفر معايير كافية لإتمام هذه المعالجة حالياً.'
                                : null,
                        ];
                    })
                    ->create([
                        'diagnosis_id' => $diagnosis->id,
                        'instructor_id' => $diagnosis->instructor_id,
                    ]);

                if ($treatmentStatus === TreatmentStatus::COMPLETED->value) {
                    $patientStatus = PatientStatus::COMPLETED->value;
                }

                $this->createAppointmentSafely($patient, $diagnosis, $treatment, $treatmentStatus);
            }

            if ($patientStatus) {
                $patient->update(['availability_status' => $patientStatus]);
            }
        }
    }

    private function createAppointmentSafely(Patient $patient, PatientDiagnose $diagnosis, Treatment $treatment, string $treatmentStatus): void
    {
        $appointmentStatus = match ($treatmentStatus) {
            TreatmentStatus::COMPLETED->value => AppointmentStatus::ATTENDED->value,
            TreatmentStatus::CANCELLED->value => AppointmentStatus::CANCELLED->value,
            default => AppointmentStatus::SCHEDULED->value,
        };

        // إعادة محاولة عند الاصطدام بالقيد الفريد student_daily_slot_unique
        // (student_id + appointment_date + slot_number) بسبب التاريخ/السلوت العشوائيين.
        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                Appointment::factory()->create([
                    'patient_id' => $patient->id,
                    'diagnosis_id' => $diagnosis->id,
                    'treatment_id' => $treatment->id,
                    'student_id' => $diagnosis->suggested_by_student_id,
                    'status' => $appointmentStatus,
                ]);

                return;
            } catch (QueryException) {
                continue;
            }
        }
    }

    private function pickTreatmentStatus(int $index): string
    {
        if ($index <= self::GUARANTEED_COMPLETED_TREATMENTS) {
            return TreatmentStatus::COMPLETED->value;
        }

        return match ($index % 5) {
            1 => TreatmentStatus::IN_PROGRESS->value,
            2 => TreatmentStatus::CANCELLED->value,
            3 => TreatmentStatus::REJECTED->value,
            4 => TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            default => TreatmentStatus::COMPLETED->value,
        };
    }

    /**
     * توزيع ثابت (وليس عشوائياً بالكامل) لحالات التشخيص عبر مرضى القسم، لضمان
     * وجود عدد كافٍ من التشخيصات المؤهلة لإنشاء معالجات (converted/finished)
     * بغض النظر عن نتيجة العشوائية — راجع GUARANTEED_COMPLETED_TREATMENTS أعلاه.
     *
     * @return array<int, string>
     */
    private function buildDiagnosisStatusPool(int $count): array
    {
        $pool = [
            ...array_fill(0, 25, DiagnosisStatus::CONVERTED_TO_TREATMENT->value),
            ...array_fill(0, 5, DiagnosisStatus::FINISHED->value),
            ...array_fill(0, 8, DiagnosisStatus::AVAILABLE->value),
            ...array_fill(0, 6, DiagnosisStatus::RESERVED->value),
            ...array_fill(0, 4, DiagnosisStatus::REJECTED->value),
        ];

        while (count($pool) < $count) {
            $pool[] = DiagnosisStatus::WAITING_APPROVAL->value;
        }

        return collect($pool)->take($count)->shuffle()->values()->all();
    }

    private function weightedEnrollmentStatus(): string
    {
        return collect([
            ...array_fill(0, 55, EnrollmentStatus::ACTIVE->value),
            ...array_fill(0, 30, EnrollmentStatus::COMPLETED->value),
            ...array_fill(0, 10, EnrollmentStatus::FAILED->value),
            ...array_fill(0, 5, EnrollmentStatus::DROPPED->value),
        ])->random();
    }
}

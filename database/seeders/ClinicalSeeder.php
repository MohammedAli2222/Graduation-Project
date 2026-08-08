<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Enums\TreatmentStatus;
use App\Models\Appointment;
use App\Models\CaseType;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * يبني السلسلة السريرية الكاملة: تشخيصات -> مواعيد -> معالجات، مع الحفاظ على
 * منطق زمني متسلسل (المعالجة تبدأ بعد الموعد، والموعد يأتي بعد التشخيص)
 * وعلى انسجام الحالات (لا تظهر معالجة "منتهية" لتشخيص ما يزال "بانتظار الموافقة" مثلاً).
 */
class ClinicalSeeder extends Seeder
{
    private const TOTAL_DIAGNOSES = 1000;
    private const TOTAL_APPOINTMENTS = 1500;

    // توزيع حالات التشخيص على المجموع 1000: مجموع CONVERTED + FINISHED = 800
    // وهو بالضبط عدد المعالجات المطلوب (كل تشخيص "محوَّل لمعالجة" أو "منتهٍ" له معالجة واحدة)
    private const STATUS_COUNTS = [
        'converted' => 550,
        'finished' => 250,
        'available' => 90,
        'reserved' => 60,
        'waiting_approval' => 30,
        'rejected' => 20,
    ];

    // توزيع فرعي لحالات المعالجة ضمن تشخيصات "محوَّل لمعالجة" (550 بالمجموع)
    private const TREATMENT_SUB_STATUS_COUNTS = [
        'in_progress' => 350,
        'waiting_instructor_approval' => 100,
        'cancelled' => 60,
        'rejected' => 40,
    ];

    private const FINAL_DIAGNOSES = [
        'التهاب لب غير ردود (Irreversible Pulpitis) يحتاج لمعالجة لبية.',
        'نخر عميق (Deep Caries) مع سلامة اللب، يحتاج ترميم كمبوزيت.',
        'التهاب دواعم السن المزمن (Chronic Periodontitis) يحتاج تقليح وتسوية جذور.',
        'انطمار ضرس العقل السفلي (Impaction) يحتاج قلع جراحي.',
        'فقد سني مفرد يحتاج تعويض بالزراعة أو جسر ثلاثي.',
        'تسوس عنقي متعدد الأسطح يحتاج حشوات كومبوزيت متعددة.',
        'سوء إطباق هيكلي يحتاج معالجة تقويمية شاملة.',
        'كسر تاجي في القاطعة العلوية يحتاج تاجاً خزفياً.',
    ];

    private array $usedAppointmentSlots = [];

    public function run(): void
    {
        $patientIds = Patient::pluck('id');
        $caseTypes = CaseType::with('course')->get();
        $studentIds = User::role('student')->pluck('id');
        $instructorIds = User::role('instructor')->pluck('id');

        if ($patientIds->isEmpty() || $caseTypes->isEmpty() || $studentIds->isEmpty() || $instructorIds->isEmpty()) {
            $this->command?->warn('يرجى تشغيل AcademicSeeder وUserSeeder وPatientSeeder قبل ClinicalSeeder.');

            return;
        }

        $diagnoses = $this->seedDiagnoses($patientIds, $caseTypes, $studentIds, $instructorIds);
        $this->seedTreatmentsAndAppointments($diagnoses, $studentIds, $instructorIds);
        $this->syncPatientAvailability($diagnoses);
    }

    /**
     * @param  Collection<int, int>  $patientIds
     * @param  Collection<int, CaseType>  $caseTypes
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<int, int>  $instructorIds
     * @return array<int, array<string, mixed>> بيانات التشخيصات المُنشأة (بما فيها القيم المحسوبة اللازمة للخطوات التالية)
     */
    private function seedDiagnoses(Collection $patientIds, Collection $caseTypes, Collection $studentIds, Collection $instructorIds): array
    {
        $statusPool = $this->buildWeightedPool(self::STATUS_COUNTS)->shuffle()->values();
        $diagnoses = [];

        $this->command?->getOutput()->writeln('توليد التشخيصات...');
        $this->command?->withProgressBar($statusPool, function (string $status) use (&$diagnoses, $patientIds, $caseTypes, $studentIds, $instructorIds): void {
            $caseType = $caseTypes->random();
            $departmentId = $caseType->course->department_id;

            $isWaitingApproval = $status === 'waiting_approval';
            // في حالة "بانتظار الموافقة" لا يوجد معيد بعد؛ كل الحالات الأخرى راجعها معيد بالفعل
            $instructorId = $isWaitingApproval ? null : $instructorIds->random();
            // التشخيصات المرفوضة تنشأ دائماً من اقتراح طالب تمت مراجعته ورفضه
            $suggestedByStudentId = $isWaitingApproval || $status === 'rejected' || random_int(0, 1) === 1
                ? $studentIds->random()
                : null;

            $createdAt = $this->pickCreatedAtForStatus($status);
            $enumStatus = $this->mapToDiagnosisStatus($status);

            $diagnosis = new PatientDiagnose([
                'patient_id' => $patientIds->random(),
                'instructor_id' => $instructorId,
                'case_type_id' => $caseType->id,
                'department_id' => $departmentId,
                'suggested_by_student_id' => $suggestedByStudentId,
                'final_diagnosis' => fake()->randomElement(self::FINAL_DIAGNOSES),
                'estimated_cost' => fake()->randomFloat(2, 50, 1500),
                'status' => $enumStatus->value,
                'rejection_reason' => $status === 'rejected'
                    ? 'الحالة معقدة جداً ولا تتناسب مع المستوى الأكاديمي للطالب.'
                    : null,
            ]);
            $diagnosis->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt]);
            $diagnosis->save();

            $diagnoses[] = [
                'id' => $diagnosis->id,
                'status' => $status,
                'patient_id' => $diagnosis->patient_id,
                'suggested_by_student_id' => $diagnosis->suggested_by_student_id,
                'created_at' => $createdAt,
            ];
        });
        $this->command?->newLine(2);

        return $diagnoses;
    }

    /**
     * @param  array<int, array<string, mixed>>  $diagnoses
     * @param  Collection<int, int>  $studentIds
     * @param  Collection<int, int>  $instructorIds
     */
    private function seedTreatmentsAndAppointments(array $diagnoses, Collection $studentIds, Collection $instructorIds): void
    {
        $eligibleForTreatment = collect($diagnoses)->filter(
            fn (array $d): bool => in_array($d['status'], ['converted', 'finished'], true)
        )->values();

        $treatmentSubStatusPool = $this->buildWeightedPool(self::TREATMENT_SUB_STATUS_COUNTS)->shuffle()->values();
        $appointmentCounts = $this->distributeAppointmentCounts($eligibleForTreatment);

        $this->command?->getOutput()->writeln('توليد المعالجات والمواعيد المرتبطة بالتشخيصات المحوَّلة...');
        $subStatusIndex = 0;

        $this->command?->withProgressBar($eligibleForTreatment, function (array $diagnosis) use (&$subStatusIndex, $treatmentSubStatusPool, $appointmentCounts, $studentIds, $instructorIds): void {
            $isFinished = $diagnosis['status'] === 'finished';
            $subStatus = $isFinished ? 'completed' : $treatmentSubStatusPool[$subStatusIndex++];
            $count = $appointmentCounts[$diagnosis['id']];
            $studentId = $diagnosis['suggested_by_student_id'] ?? $studentIds->random();

            // نبني تواريخ الجلسات أولاً بشكل متسلسل بعد تاريخ التشخيص، ثم نحسب تواريخ
            // المعالجة اعتماداً عليها لضمان: تاريخ بدء المعالجة > تاريخ أول موعد
            $visitDates = $this->buildSequentialVisitDates($diagnosis['created_at'], $count);

            $startDate = (clone $visitDates[0])->addDays(random_int(0, 2));
            $endDate = $subStatus === 'completed'
                ? (clone $visitDates[count($visitDates) - 1])->addDays(random_int(0, 2))
                : null;

            $treatment = new Treatment([
                'diagnosis_id' => $diagnosis['id'],
                'instructor_id' => $instructorIds->random(),
                'status' => $this->mapToTreatmentStatus($subStatus)->value,
                'rejection_reason' => $subStatus === 'rejected'
                    ? 'لا تتوفر معايير كافية لإتمام هذه المعالجة حالياً.'
                    : null,
                'instructor_notes' => random_int(1, 100) <= 30
                    ? 'يرجى الانتباه للعزل الجيد أثناء تطبيق التخريش الحمضي.'
                    : null,
            ]);
            $treatment->forceFill([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'created_at' => $startDate,
                'updated_at' => $endDate ?? $startDate,
            ]);
            $treatment->save();

            $this->createAppointmentsForVisits($diagnosis, $treatment, $subStatus, $visitDates, $studentId);
        });
        $this->command?->newLine(2);

        $this->seedReservedAppointments(collect($diagnoses)->where('status', 'reserved')->values());
    }

    /**
     * @param  array<string, mixed>  $diagnosis
     * @param  array<int, Carbon>  $visitDates
     */
    private function createAppointmentsForVisits(array $diagnosis, Treatment $treatment, string $subStatus, array $visitDates, int $studentId): void
    {
        $lastIndex = count($visitDates) - 1;

        foreach ($visitDates as $index => $date) {
            $isLast = $index === $lastIndex;

            $status = match (true) {
                $subStatus === 'in_progress' && $isLast && $lastIndex > 0 => AppointmentStatus::SCHEDULED,
                $subStatus === 'cancelled' && $isLast => AppointmentStatus::CANCELLED,
                ! $isLast && $subStatus === 'in_progress' && random_int(1, 100) <= 5 => AppointmentStatus::MISSED,
                default => AppointmentStatus::ATTENDED,
            };

            // الموعد المُجدوَل مستقبلاً (المتابعة القادمة) يجب أن يكون بتاريخ لاحق فعلاً لليوم الحالي
            $appointmentDate = $status === AppointmentStatus::SCHEDULED
                ? Carbon::now()->addDays(random_int(1, 20))
                : $date;

            $this->createAppointment(
                patientId: $diagnosis['patient_id'],
                studentId: $studentId,
                diagnosisId: $diagnosis['id'],
                treatmentId: $treatment->id,
                date: $appointmentDate,
                status: $status,
            );
        }
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $reservedDiagnoses
     */
    private function seedReservedAppointments(Collection $reservedDiagnoses): void
    {
        $studentIds = User::role('student')->pluck('id');

        foreach ($reservedDiagnoses as $diagnosis) {
            $this->createAppointment(
                patientId: $diagnosis['patient_id'],
                studentId: $diagnosis['suggested_by_student_id'] ?? $studentIds->random(),
                diagnosisId: $diagnosis['id'],
                treatmentId: null,
                date: Carbon::now()->addDays(random_int(1, 15)),
                status: AppointmentStatus::SCHEDULED,
            );
        }
    }

    private function createAppointment(int $patientId, int $studentId, int $diagnosisId, ?int $treatmentId, Carbon $date, AppointmentStatus $status): void
    {
        [$date, $slotNumber] = $this->reserveFreeSlot($studentId, $date);

        $appointment = new Appointment([
            'patient_id' => $patientId,
            'student_id' => $studentId,
            'diagnosis_id' => $diagnosisId,
            'treatment_id' => $treatmentId,
            'slot_number' => $slotNumber,
            'appointment_date' => $date->format('Y-m-d'),
            'status' => $status->value,
        ]);
        $appointment->forceFill(['created_at' => $date, 'updated_at' => $date]);
        $appointment->save();
    }

    /**
     * يتفادى خرق قيد التفرّد (student_id, appointment_date, slot_number) عبر تتبّع
     * الحجوزات المُستخدمة في الذاكرة، مع الانتقال لليوم التالي عند امتلاء كل الفتحات الأربع.
     *
     * @return array{0: Carbon, 1: int}
     */
    private function reserveFreeSlot(int $studentId, Carbon $date): array
    {
        $date = clone $date;

        for ($attempt = 0; $attempt < 30; $attempt++) {
            for ($slot = 1; $slot <= 4; $slot++) {
                $key = $studentId . '|' . $date->format('Y-m-d') . '|' . $slot;

                if (! isset($this->usedAppointmentSlots[$key])) {
                    $this->usedAppointmentSlots[$key] = true;

                    return [$date, $slot];
                }
            }

            $date->addDay();
        }

        // احتياط أخير (نادر الحدوث إحصائياً) في حال استُنفدت كل المحاولات
        return [$date, random_int(1, 4)];
    }

    /**
     * @param  array<int, Carbon>  $visitDates
     * @return array<int, Carbon>
     */
    private function buildSequentialVisitDates(Carbon $diagnosisCreatedAt, int $count): array
    {
        $dates = [];
        $current = (clone $diagnosisCreatedAt)->addDays(random_int(2, 8));

        for ($i = 0; $i < $count; $i++) {
            if ($i > 0) {
                $current = (clone $current)->addDays(random_int(7, 21));
            }

            $dates[] = clone $current;
        }

        return $dates;
    }

    /**
     * يوزّع 1500 موعد إجمالاً: 60 موعد حجز لتشخيصات "reserved" (تُعالَج في
     * seedReservedAppointments)، وموعد واحد أساسي لكل تشخيص مؤهل آخر (550 + 250 = 800)،
     * والباقي (640) يُوزَّع عشوائياً كزيارات متابعة إضافية على نفس المجموعة.
     *
     * @param  Collection<int, array<string, mixed>>  $eligibleForTreatment
     * @return array<int, int> معرّف التشخيص => عدد المواعيد المخصصة له
     */
    private function distributeAppointmentCounts(Collection $eligibleForTreatment): array
    {
        $counts = [];
        foreach ($eligibleForTreatment as $diagnosis) {
            $counts[$diagnosis['id']] = 1;
        }

        // إجمالي المواعيد المطلوبة لهذه المجموعة = 1500 ناقص موعد الحجز الواحد
        // لكل تشخيص "محجوز" (reserved)، والذي يُعالَج بشكل منفصل في seedReservedAppointments
        $reservedCount = self::STATUS_COUNTS['reserved'];
        $remaining = self::TOTAL_APPOINTMENTS - $reservedCount - $eligibleForTreatment->count();

        $ids = $eligibleForTreatment->pluck('id')->all();
        while ($remaining > 0 && ! empty($ids)) {
            $randomId = $ids[array_rand($ids)];
            $counts[$randomId]++;
            $remaining--;
        }

        return $counts;
    }

    /**
     * @param  array<string, int>  $weights
     * @return Collection<int, string>
     */
    private function buildWeightedPool(array $weights): Collection
    {
        $pool = [];
        foreach ($weights as $value => $weight) {
            $pool = array_merge($pool, array_fill(0, $weight, $value));
        }

        return collect($pool);
    }

    private function pickCreatedAtForStatus(string $status): Carbon
    {
        return match ($status) {
            // الحالات المنتهية/المحوَّلة أقدم زمنياً حتى تتّسع لسلسلة مواعيد ومعالجة كاملة قبل اليوم الحالي
            'finished' => Carbon::now()->subDays(random_int(90, 180)),
            'converted' => Carbon::now()->subDays(random_int(30, 150)),
            'reserved' => Carbon::now()->subDays(random_int(5, 20)),
            'rejected' => Carbon::now()->subDays(random_int(10, 60)),
            'available' => Carbon::now()->subDays(random_int(1, 25)),
            default => Carbon::now()->subDays(random_int(0, 5)), // waiting_approval
        };
    }

    private function mapToDiagnosisStatus(string $status): DiagnosisStatus
    {
        return match ($status) {
            'converted' => DiagnosisStatus::CONVERTED_TO_TREATMENT,
            'finished' => DiagnosisStatus::FINISHED,
            'available' => DiagnosisStatus::AVAILABLE,
            'reserved' => DiagnosisStatus::RESERVED,
            'waiting_approval' => DiagnosisStatus::WAITING_APPROVAL,
            default => DiagnosisStatus::REJECTED,
        };
    }

    private function mapToTreatmentStatus(string $subStatus): TreatmentStatus
    {
        return match ($subStatus) {
            'in_progress' => TreatmentStatus::IN_PROGRESS,
            'waiting_instructor_approval' => TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL,
            'cancelled' => TreatmentStatus::CANCELLED,
            'rejected' => TreatmentStatus::REJECTED,
            default => TreatmentStatus::COMPLETED,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $diagnoses
     */
    private function syncPatientAvailability(array $diagnoses): void
    {
        $this->command?->getOutput()->writeln('تحديث حالة توفر المرضى بما يعكس أحدث تشخيص لهم...');

        $latestStatusPerPatient = [];
        foreach ($diagnoses as $diagnosis) {
            $latestStatusPerPatient[$diagnosis['patient_id']] = $diagnosis['status'];
        }

        foreach ($latestStatusPerPatient as $patientId => $status) {
            $availability = match ($status) {
                'finished' => PatientStatus::COMPLETED,
                'converted', 'reserved' => PatientStatus::FULLY_RESERVED,
                'available' => PatientStatus::AVAILABLE,
                default => null, // waiting_approval / rejected تبقي المريض كما هو (بانتظار التشخيص)
            };

            if ($availability !== null) {
                Patient::whereKey($patientId)->update(['availability_status' => $availability->value]);
            }
        }
    }
}

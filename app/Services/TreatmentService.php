<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
use App\Events\TreatmentCompletedByStudentEvent;
use App\Exceptions\PendingAppointmentsException;
use App\Exceptions\TreatmentNotInProgressException;
use App\Repositories\AppointmentRepository;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use App\Repositories\TreatmentRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class TreatmentService
{
    protected $treatmentRepo;
    protected $appointmentRepo;
    protected $mediaService;
    protected $appointmentser;
    protected $patientRepo;
    protected $diagnosisRepo;

    public function __construct(
        TreatmentRepository $treatmentRepo,
        AppointmentRepository $appointmentRepo,
        AppointmentService $appointmentser,
        MediaService $mediaService,
        PatientRepository $patientRepo,
        DiagnosisRepository $diagnosisRepo
    ) {
        $this->treatmentRepo = $treatmentRepo;
        $this->mediaService = $mediaService;
        $this->appointmentRepo = $appointmentRepo;
        $this->appointmentser = $appointmentser;
        $this->patientRepo = $patientRepo;
        $this->diagnosisRepo = $diagnosisRepo;
    }

    // public function bookFollowUpAppointment(array $data)
    // {
    //     $treatment = $this->treatmentRepo->find($data['treatment_id']);

    //     if (! $treatment) {
    //         throw new Exception('Treatment record not found.');
    //     }

    //     if ($treatment->status !== TreatmentStatus::IN_PROGRESS) {
    //         throw new Exception('Cannot book a follow-up for inactive case.');
    //     }

    //     $firstApp = $treatment->appointments()->first();
    //     if ($firstApp && $firstApp->student_id !== auth()->id()) {
    //         throw new Exception('Unauthorized! Not your case.');
    //     }

    //     $lastAppointment = $treatment->appointments()
    //         ->orderBy('appointment_date', 'desc')
    //         ->first();

    //     $newDate = Carbon::parse($data['appointment_date']);

    //     if ($lastAppointment) {
    //         $lastDate = Carbon::parse($lastAppointment->appointment_date);

    //         if ($newDate->lessThanOrEqualTo($lastDate)) {
    //             throw new \Exception(
    //                 "The follow-up date must be after the date of the previous appointment (" .
    //                     $lastDate->format('Y-m-d') . ")."
    //             );
    //         }
    //     }
    //     $dateOnly = Carbon::parse($data['appointment_date'])->format('Y-m-d');

    //     $this->appointmentser->validateAppointmentTiming(
    //         $dateOnly,
    //         auth()->id(),
    //         $treatment->diagnosis->department_id,
    //         (int) $data['slot_number']
    //     );

    //     return $this->appointmentRepo->create([
    //         'patient_id' => $treatment->diagnosis->patient_id,
    //         'student_id' => auth()->id(),
    //         'diagnosis_id' => $treatment->diagnosis_id,
    //         'treatment_id' => $treatment->id,
    //         'appointment_date' => $dateOnly,
    //         'slot_number' => $data['slot_number'],
    //         'status' => AppointmentStatus::SCHEDULED->value,
    //     ]);
    // }

    private function getSlotStartTime(int $startSlot): \Carbon\Carbon
    {
        $times = [
            1 => '08:00',
            2 => '10:30',
            3 => '13:00',
            4 => '15:30',
        ];

        $time = $times[$startSlot] ?? '08:00';
        return \Carbon\Carbon::createFromFormat('H:i', $time);
    }
    public function bookFollowUpAppointment(array $data)
    {
        $treatment = $this->treatmentRepo->find($data['treatment_id']);
        if (! $treatment || $treatment->status !== TreatmentStatus::IN_PROGRESS) {
            throw new \Exception('Invalid or inactive treatment.');
        }

        if ($treatment->appointments()->where('student_id', auth()->id())->doesntExist()) {
            throw new \Exception('Unauthorized! Not your case.');
        }

        $lastAppointment = $treatment->appointments()->orderBy('appointment_date', 'desc')->first();
        $newDate = Carbon::parse($data['appointment_date']);
        if ($lastAppointment && $newDate->lessThanOrEqualTo(Carbon::parse($lastAppointment->appointment_date))) {
            throw new \Exception("Follow-up date must be after " . $lastAppointment->appointment_date->format('Y-m-d'));
        }

        $dateOnly = $newDate->format('Y-m-d');
        $slotNumber = (int) $data['slot_number'];
        $slotsNeeded = 1;

        $this->appointmentser->validateAppointmentTiming(
            $dateOnly,
            auth()->id(),
            $treatment->diagnosis->department_id,
            $slotNumber
        );

        $dailyLimit = (int) config('clinic.max_student_appointments_per_day');

        $existingCount = $this->appointmentRepo->getActiveAppointmentsCountForStudent(auth()->id(), $dateOnly);
        if (($existingCount + $slotsNeeded) > $dailyLimit) {
            throw new \Exception("You have reached the daily limit of {$dailyLimit} appointment slots.");
        }

        return $this->appointmentRepo->create([
            'patient_id'   => $treatment->diagnosis->patient_id,
            'student_id'   => auth()->id(),
            'diagnosis_id' => $treatment->diagnosis_id,
            'treatment_id' => $treatment->id,
            'appointment_date' => $dateOnly,
            'slot_number'  => $slotNumber,
            'status'       => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function completeTreatmentFromStudent(array $data)
    {
        $treatment = DB::transaction(function () use ($data) {
            $treatment = $this->treatmentRepo->find($data['treatment_id']);

            if (! $treatment) {
                throw new Exception('Treatment record not found.', 404);
            }

            $st = $treatment->status;
            if (
                $st !== TreatmentStatus::IN_PROGRESS
                && $st !== TreatmentStatus::IN_PROGRESS->value
            ) {
                throw new TreatmentNotInProgressException();
            }

            // التحقق من الملكية عبر وجود أي موعد لهذا الطالب ضمن الحالة، بدل
            // الاكتفاء بأول موعد فقط (قد يكون أُلغي أو أُعيد ترتيبه).
            $ownsTreatment = $treatment->appointments()
                ->where('student_id', auth()->id())
                ->exists();

            if (! $ownsTreatment) {
                throw new Exception('Unauthorized! Not your treatment.', 403);
            }

            $hasUpcoming = $treatment->appointments()
                ->where('status', AppointmentStatus::SCHEDULED->value)
                ->exists();

            if ($hasUpcoming) {
                throw new PendingAppointmentsException();
            }

            // حارس دورة الحياة: لا تُرفع صور "بعد" على حالة بلا صور "قبل"،
            // فالمقارنة بينهما هي أساس تقييم المعيد.
            if ($treatment->getMedia('before_treatment_images')->isEmpty()) {
                throw new Exception('This treatment has no before-treatment images and cannot be completed.', 422);
            }

            if (empty($data['after_images'])) {
                throw new Exception('After-treatment images are required to complete the treatment.', 422);
            }

            $this->mediaService->upload(
                $treatment,
                $data['after_images'],
                'after_treatment_images'
            );

            $treatment->update([
                'status' => TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            ]);

            $this->treatmentRepo->clearStudentProgressCache(auth()->id());

            return $treatment->load(['diagnosis', 'appointments']);
        });

        // إطلاق الحدث بعد نجاح المعاملة لإشعار المدرّس المسؤول دون تأخير استجابة الـ API الرئيسية
        TreatmentCompletedByStudentEvent::dispatch($treatment);

        return $treatment;
    }

    public function getPatientTreatmentHistory(int $patientId)
    {
        $patientExists = $this->patientRepo->FindOrFail($patientId);

        if (! $patientExists) {
            throw new ModelNotFoundException('Patient not found.');
        }

        $hasRelation = $this->appointmentRepo->hasAppointmentWithPatient(
            auth()->id(),
            $patientId
        );

        if (! $hasRelation) {
            throw new Exception('Unauthorized! No appointment found.');
        }

        return $this->treatmentRepo->getHistoryByPatientId($patientId);
    }

    public function cancelAppointment(int $appointmentId)
    {
        $appointment = $this->appointmentRepo->findById($appointmentId);
        if (! $appointment) {
            throw new ModelNotFoundException('Appointment not found.');
        }

        // التحقق من أن الموعد يعود لنفس الطالب المصادق عليه لمنع أي طالب آخر من إلغائه
        if ($appointment->student_id !== auth()->id()) {
            throw new Exception('Unauthorized! Not your appointment.', 403);
        }

        if ($appointment->status === AppointmentStatus::ATTENDED) {
            throw new Exception('Cannot cancel started treatment.');
        }

        $now = \Carbon\Carbon::now('Asia/Damascus');
        $appointmentDate = \Carbon\Carbon::parse($appointment->appointment_date, 'Asia/Damascus');

        if ($appointmentDate->isToday()) {
            $startTime = $this->getSlotStartTime($appointment->slot_number);

            $fullStartDateTime = $appointmentDate->copy()->setTimeFrom($startTime);

            if ($now->copy()->addHours(2)->greaterThan($fullStartDateTime)) {
                throw new \Exception('Appointments cannot be cancelled within 2 hours of the scheduled time.');
            }
        }

        return DB::transaction(function () use ($appointment, $appointmentId) {
            $this->appointmentRepo->updateStatus(
                $appointmentId,
                AppointmentStatus::CANCELLED->value
            );

            if ($appointment->diagnosis_id) {
                $activeCount = $this->appointmentRepo
                    ->countActiveAppointmentsForDiagnosis($appointment->diagnosis_id);

                if ($activeCount === 0) {
                    $this->diagnosisRepo->makeAvailable($appointment->diagnosis_id);

                    $this->appointmentRepo->updatePatientAvailabilityStatus($appointment->patient_id);
                }
            }

            return true;
        });
    }

    public function rollbackStartedTreatment(int $treatmentId)
    {
        $treatment = $this->treatmentRepo->find($treatmentId);

        $this->validateRollback($treatment, $treatmentId);

        return DB::transaction(function () use ($treatment, $treatmentId) {
            $this->appointmentRepo->cancelCurrentAttendedAppointment($treatmentId);

            $this->treatmentRepo->updateStatus(
                $treatmentId,
                TreatmentStatus::CANCELLED->value
            );

            if ($treatment->diagnosis_id) {
                $activeCount = $this->appointmentRepo
                    ->countActiveAppointmentsForDiagnosis($treatment->diagnosis_id);

                if ($activeCount === 0) {
                    $this->diagnosisRepo->makeAvailable($treatment->diagnosis_id);

                    $this->appointmentRepo->updatePatientAvailabilityStatus($treatment->diagnosis->patient_id);
                }
            }

            return true;
        });
    }

    public function getPendingTreatmentsListForInstructor($user, int $perPage = 10, ?int $groupId = null)
    {
        $instructorProfile = $user->instructorProfile;

        if (! $instructorProfile) {
            throw new Exception('Unauthorized! Profile must be an instructor.');
        }

        return $this->treatmentRepo->getPendingApprovalsListForInstructor(
            $instructorProfile->id,
            $perPage,
            $groupId
        );
    }

    public function getTreatmentDetailsForInstructor(int $treatmentId, $user)
    {
        $instructorProfile = $user->instructorProfile;

        if (! $instructorProfile) {
            throw new Exception('Unauthorized! Profile must be an instructor.');
        }

        $treatment = $this->treatmentRepo->getTreatmentDetailsForInstructor(
            $treatmentId,
            $instructorProfile->id
        );

        if (! $treatment) {
            throw new Exception('Treatment case not found or access denied.');
        }

        return $treatment;
    }

    public function getStudentDashboardStats($user)
    {
        if (! $user->studentProfile) {
            throw new Exception('Unauthorized! Profile must be a student.');
        }

        $rawStats = $this->treatmentRepo->getStudentProgressStats($user->id);

        if (! $rawStats) {
            throw new Exception('Student profile configuration missing.');
        }

        $totalRequired = collect($rawStats['types_detail'])->sum('required_to_pass');
        $totalCompleted = collect($rawStats['types_detail'])->sum('completed_count');

        $progressPct = $totalRequired > 0
            ? round($totalCompleted / $totalRequired * 100)
            : 0;

        return [
            'general_stats' => [
                'total_student_actions' => $rawStats['treatments']->total ?? 0,
                'total_completed_cases' => $totalCompleted,
                'total_required_cases' => $totalRequired,
                'pending_approval' => $rawStats['treatments']->pending ?? 0,
                'rejected_cases' => $rawStats['treatments']->rejected ?? 0,
                'general_progress_pct' => $progressPct,
            ],
            'attendance_stats' => $this->calculateAttendanceStats($rawStats),
            'breakdown_by_course' => $this->formatCourseBreakdown($rawStats),
        ];
    }

    public function getStudentCases($user, $statusType, int $perPage)
    {
        if (! $user->studentProfile) {
            throw new Exception('Unauthorized! Profile must be a student.');
        }

        $statusType = in_array($statusType, ['completed', 'remaining'])
            ? $statusType
            : 'remaining';

        $paginatedData = $this->treatmentRepo
            ->getStudentTreatmentsList($user->id, $statusType, $perPage);

        return [
            'cases' => $this->formatStudentCasesList($paginatedData),
            'pagination' => [
                'total' => $paginatedData->total(),
                'count' => $paginatedData->count(),
                'per_page' => $paginatedData->perPage(),
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
            ],
        ];
    }

    /**
     * Private methods placed at the end of the class.
     */

    private function validateRollback($treatment, int $treatmentId): void
    {
        if (! $treatment) {
            throw new ModelNotFoundException('Treatment record not found.');
        }

        // التحقق من أن أول موعد ضمن هذه الحالة يعود لنفس الطالب المصادق عليه لمنع أي طالب آخر من التراجع عنها
        $firstAppointment = $treatment->appointments()->first();
        if ($firstAppointment && $firstAppointment->student_id !== auth()->id()) {
            throw new Exception('Unauthorized! Not your treatment.', 403);
        }

        $totalAppointments = $this->appointmentRepo
            ->countAppointmentsForTreatment($treatmentId);

        if ($totalAppointments > 1) {
            throw new Exception('Cannot rollback subsequent appointments.');
        }

        $this->checkRollbackConstraints($treatment);
    }

    private function checkRollbackConstraints($treatment): void
    {
        $now = \Carbon\Carbon::now('Asia/Damascus');

        $createdAt = \Carbon\Carbon::parse($treatment->created_at)->timezone('Asia/Damascus');

        if ($createdAt->diffInMinutes($now) > 30) {
            throw new \Exception('Cancellation window (30 mins) expired.');
        }

        $st = $treatment->status;
        if (
            $st === TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL
            || $st === TreatmentStatus::COMPLETED
        ) {
            throw new Exception('Treatment already submitted or completed.');
        }

        if ($treatment->instructor_notes !== null || $treatment->instructor_id !== null) {
            throw new Exception('Treatment has already been reviewed.');
        }
    }

    private function calculateAttendanceStats(array $rawStats): array
    {
        $totalAppoints = $rawStats['appointments']->total ?? 0;
        $attendedAppoints = $rawStats['appointments']->attended ?? 0;

        $attendancePct = $totalAppoints > 0
            ? round($attendedAppoints / $totalAppoints * 100)
            : 0;

        return [
            'total_appointments' => $totalAppoints,
            'attended_appointments' => $attendedAppoints,
            'attendance_percentage' => $attendancePct,
        ];
    }

    private function formatCourseBreakdown(array $rawStats): \Illuminate\Support\Collection
    {
        return collect($rawStats['types_detail'])->map(function ($item) {
            $required = (int) ($item->required_to_pass ?? 0);
            $done = (int) ($item->completed_count ?? 0);
            // required_count قد يكون 0 فعلياً (لا مجرد null) إن ضبطه رئيس القسم
            // كذلك؛ القسمة عليه مباشرة ترمي DivisionByZeroError في PHP 8.
            $pct = $required > 0 ? round($done / $required * 100) : 0;

            return [
                'case_type_id' => $item->type_id,
                'case_type_name' => $item->case_type_name,
                'course_name' => $item->course_name,
                'completed_count' => $done,
                'required_count' => $required,
                'remaining_count' => max(0, $required - $done),
                'in_progress_count' => $item->in_progress_count ?? 0,
                'progress_percentage' => $pct > 100 ? 100 : $pct,
            ];
        });
    }

    private function formatStudentCasesList($paginatedData): array
    {
        return collect($paginatedData->items())->map(function ($treatment) {
            $diag = $treatment->diagnosis;
            $p = $diag?->patient;
            $ct = $diag?->caseType;

            return [
                'treatment_id' => $treatment->id,
                'status' => $treatment->status,
                'start_date' => $treatment->start_date
                    ? Carbon::parse($treatment->start_date)->format('Y-m-d H:i A')
                    : null,
                'end_date' => $treatment->end_date
                    ? Carbon::parse($treatment->end_date)->format('Y-m-d H:i A')
                    : null,
                'patient' => [
                    'id' => $p?->id,
                    'full_name' => $p?->full_name ?? 'مريض غير معروف',
                ],
                'medical_info' => [
                    'case_type_name' => $ct?->name,
                    'course_name' => $ct?->course?->name,
                ],
            ];
        })->all();
    }
}

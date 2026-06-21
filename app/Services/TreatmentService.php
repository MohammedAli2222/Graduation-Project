<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\TreatmentStatus;
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

    public function bookFollowUpAppointment(array $data)
    {
        $treatment = $this->treatmentRepo->find($data['treatment_id']);

        if (! $treatment) {
            throw new Exception('Treatment record not found.');
        }

        if ($treatment->status !== TreatmentStatus::IN_PROGRESS) {
            throw new Exception('Cannot book a follow-up for inactive case.');
        }

        $firstApp = $treatment->appointments()->first();
        if ($firstApp && $firstApp->student_id !== auth()->id()) {
            throw new Exception('Unauthorized! Not your case.');
        }

        $lastAppointment = $treatment->appointments()
            ->orderBy('appointment_date', 'desc')
            ->first();

        $newDate = Carbon::parse($data['appointment_date']);

        if ($lastAppointment) {
            $lastDate = Carbon::parse($lastAppointment->appointment_date);

            if ($newDate->lessThanOrEqualTo($lastDate)) {
                throw new \Exception(
                    "The follow-up date must be after the date of the previous appointment (" .
                        $lastDate->format('Y-m-d') . ")."
                );
            }
        }
        $dateOnly = Carbon::parse($data['appointment_date'])->format('Y-m-d');

        $this->appointmentser->validateAppointmentTiming(
            $dateOnly,
            auth()->id(),
            $treatment->diagnosis->department_id,
            (int) $data['slot_number']
        );

        return $this->appointmentRepo->create([
            'patient_id' => $treatment->diagnosis->patient_id,
            'student_id' => auth()->id(),
            'diagnosis_id' => $treatment->diagnosis_id,
            'treatment_id' => $treatment->id,
            'appointment_date' => $dateOnly,
            'slot_number' => $data['slot_number'],
            'status' => AppointmentStatus::SCHEDULED->value,
        ]);
    }

    public function completeTreatmentFromStudent(array $data)
    {
        return DB::transaction(function () use ($data) {
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

            $firstApp = $treatment->appointments()->first();
            if ($firstApp && $firstApp->student_id !== auth()->id()) {
                throw new Exception('Unauthorized! Not your treatment.');
            }

            $hasUpcoming = $treatment->appointments()
                ->where('status', AppointmentStatus::SCHEDULED->value)
                ->exists();

            if ($hasUpcoming) {
                throw new PendingAppointmentsException();
            }

            $this->mediaService->upload(
                $treatment,
                $data['after_images'],
                'after_treatment_images'
            );

            $treatment->update([
                'status' => TreatmentStatus::WAITING_INSTRUCTOR_APPROVAL->value,
            ]);

            return $treatment->load(['diagnosis', 'appointments']);
        });
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
        if ($appointment->status === AppointmentStatus::ATTENDED) {
            throw new Exception('Cannot cancel started treatment.');
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
                }
            }

            return true;
        });
    }

    public function getPendingTreatmentsListForInstructor($user, int $perPage = 10)
    {
        $instructorProfile = $user->instructorProfile;

        if (! $instructorProfile) {
            throw new Exception('Unauthorized! Profile must be an instructor.');
        }

        return $this->treatmentRepo->getPendingApprovalsListForInstructor(
            $instructorProfile->id,
            $perPage
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

        $totalAppointments = $this->appointmentRepo
            ->countAppointmentsForTreatment($treatmentId);

        if ($totalAppointments > 1) {
            throw new Exception('Cannot rollback subsequent appointments.');
        }

        $this->checkRollbackConstraints($treatment);
    }

    private function checkRollbackConstraints($treatment): void
    {
        if ($treatment->created_at->diffInMinutes(now()) > 30) {
            throw new Exception('Cancellation window (30 mins) expired.');
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
            $required = $item->required_to_pass ?? 1;
            $done = $item->completed_count ?? 0;
            $pct = round($done / $required * 100);

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

<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\DiagnosisStatus;
use App\Enums\PatientStatus;
use App\Models\Patient;
use App\Models\PatientDiagnose;
use App\Repositories\AppointmentRepository;
use App\Repositories\DiagnosisRepository;
use App\Repositories\PatientRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    protected $appointmentRepo;

    protected $diagnosisRepo;

    protected $patientRepo;

    public function __construct(
        AppointmentRepository $appointmentRepo,
        DiagnosisRepository $diagnosisRepo,
        PatientRepository $patientRepo
    ) {
        $this->appointmentRepo = $appointmentRepo;
        $this->diagnosisRepo = $diagnosisRepo;
        $this->patientRepo = $patientRepo;
    }



    public function reserveCase(array $data)
    {
        $diagnosisId = $data['diagnosis_id'];
        $lock = Cache::lock('lock:diagnosis:' . $diagnosisId, 10);

        try {
            $lock->block(3);
            return DB::transaction(function () use ($data) {

                $date = Carbon::parse($data['appointment_date']);

                // منع الحجز في عطلة نهاية الأسبوع
                if ($date->isFriday() || $date->isSaturday()) {
                    throw ValidationException::withMessages([
                        'appointment_date' => ['Appointments can only be booked on university working days (Sunday to Thursday).'],
                    ]);
                }
                $student = auth()->user();

                $diagnosis = $this->diagnosisRepo->findAvailableDiagnosis($data['diagnosis_id']);
                if (! $diagnosis) {
                    throw ValidationException::withMessages(['diagnosis' => ['This case is no longer available.']]);
                }

                $startSlot = (int) $data['slot_number'];
                $slotsNeeded = (int) $diagnosis->caseType->slots_needed;
                $endSlot = $startSlot + $slotsNeeded - 1;
                $dateOnly = Carbon::parse($data['appointment_date'])->format('Y-m-d');

                // 1. فحص الحد اليومي (المجموع وليس العدد)
                $usedSlots = $this->appointmentRepo->getStudentDailyUsage($student->id, $dateOnly);
                if (($usedSlots + $slotsNeeded) > 2) {
                    throw ValidationException::withMessages(['appointment_date' => ["Exceeds daily limit of 2 slots."]]);
                }

                // 2. فحص تعارض الطالب (باستخدام معادلة النطاقات)
                if ($this->appointmentRepo->hasOverlap($student->id, $dateOnly, $startSlot, $endSlot, 'student_id')) {
                    throw ValidationException::withMessages(['slot_number' => ['You have a scheduling conflict.']]);
                }

                // 3. فحص تعارض المريض
                if ($this->appointmentRepo->hasOverlap($diagnosis->patient_id, $dateOnly, $startSlot, $endSlot, 'patient_id')) {
                    throw ValidationException::withMessages(['appointment_date' => ['Patient is booked at this time.']]);
                }

                // 4. فحص هل القسم ممتلئ؟
                if ($this->appointmentRepo->isDepartmentFull($diagnosis->department_id, $dateOnly, $startSlot, $endSlot)) {
                    throw ValidationException::withMessages([
                        'appointment_date' => ['This time slot is fully booked. All chairs in this department are occupied.'],
                    ]);
                }

                // 4. إنشاء سجل واحد فقط!
                $appointment = $this->appointmentRepo->create([
                    'patient_id'   => $diagnosis->patient_id,
                    'student_id'   => $student->id,
                    'diagnosis_id' => $diagnosis->id,
                    'start_slot'   => $startSlot,
                    'end_slot'     => $endSlot,
                    'slots_count'  => $slotsNeeded,
                    'appointment_date' => $dateOnly,
                    'status'       => AppointmentStatus::SCHEDULED->value,
                ]);

                $diagnosis->update(['status' => DiagnosisStatus::RESERVED->value]);
                $this->updatePatientStatusIfAllDiagnosesReserved($diagnosis->patient_id);

                return [$appointment]; // نرجع مصفوفة لتتوافق مع الـ Resource
            });
        } finally {
            optional($lock)->release();
        }
    }

    public function getStudentAppointments(int $studentId)
    {
        return $this->appointmentRepo->getStudentAppointments($studentId);
    }

    public function getStudentAppointmentHistory(int $studentId)
    {
        return $this->appointmentRepo->getStudentAppointmentHistory($studentId);
    }

    public function updatePatientStatusIfAllDiagnosesReserved(int $patientId)
    {
        $hasAvailableDiagnoses = PatientDiagnose::where('patient_id', $patientId)
            ->where('status', DiagnosisStatus::AVAILABLE->value)
            ->exists();

        if (!$hasAvailableDiagnoses) {
            Patient::where('id', $patientId)
                ->update(['availability_status' => PatientStatus::FULLY_RESERVED->value]);
        }
    }
}

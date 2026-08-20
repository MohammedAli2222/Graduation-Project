<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewVisitRequest;
use App\Http\Requests\StorePatientRequest;
use App\Http\Requests\UpdatePatientRequest;
use App\Http\Resources\PatientResource;
use App\Services\PatientService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ReceptionistController extends Controller
{
    protected PatientService $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    /**
     * تسجيل مريض جديد من شاشة الاستقبال.
     *
     * الاستجابة فورية (201) ولا تنتظر معالجة الصور إطلاقاً: الصور تُخزَّن
     * مؤقتاً ثم يتولّى ProcessPatientImagesJob نقلها لمكتبة الوسائط وتوليد
     * تحويلاتها في الخلفية. لذلك حقل الوسائط في الرد يعود فارغاً وهذا مقصود.
     */
    public function store(StorePatientRequest $request)
    {
        try {
            $patient = $this->patientService->registerPatient(
                $request->validated(),
                [
                    'id_card' => $request->file('id_card'),
                    'clinical_images' => $request->file('clinical_images'),
                    'x_ray_images' => $request->file('x_ray_images'),
                ]
            );

            // لا load إضافي هنا: الخدمة أعادت المريض مع medicalHistory فقط،
            // وأي تحميل لعلاقة media سيكلّف استعلاماً بلا نتيجة في هذه اللحظة.
            return response_success(
                new PatientResource($patient),
                201,
                'تم تسجيل المريض بنجاح، وجاري معالجة الصور في الخلفية.'
            );
        } catch (\Exception $e) {
            // نحترم كود الخطأ القادم من طبقة الخدمة (403/404/422) بدل
            // تحويل كل شيء إلى 500 كما كان سابقاً.
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? (int) $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q'));
        if ($term === '') {
            return response_error(null, 400, 'Search term is required');
        }

        $patients = $this->patientService->searchPatients($term);

        return response_success([
            'patients' => PatientResource::collection($patients->items()),
            'pagination' => [
                'total' => $patients->total(),
                'count' => $patients->count(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
            ],
        ], 200, 'Search results.');
    }

    public function show($id)
    {
        try {
            $patient = $this->patientService->getPatientProfile($id);
            // $patient->load('medicalHistory');
            return response_success(new PatientResource($patient), 200, 'Patient profile fetched.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'Patient not found.');
        }
    }

    public function receptionistWaiting(Request $request)
    {
        $patients = $this->patientService->getReceptionistWaitingPatients(
            (string) $request->query('status', 'all')
        );

        if ($patients->isEmpty()) {
            return response_success([], 200, 'Waiting list is currently empty.');
        }

        return response_success([
            'patients' => PatientResource::collection($patients->items()),
            'pagination' => [
                'total' => $patients->total(),
                'count' => $patients->count(),
                'per_page' => $patients->perPage(),
                'current_page' => $patients->currentPage(),
                'last_page' => $patients->lastPage(),
            ],
        ], 200, 'Waiting list fetched successfully.');
    }

    public function update(UpdatePatientRequest $request, $id)
    {
        try {
            $patient = $this->patientService->updatePatient(
                $id,
                $request->validated(),
                $request->file('images')
            );

            return response_success(new PatientResource($patient), 200, 'Updated successfully.');
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'patient id not found');
        } catch (\Exception $e) {
            return response_error(null, 500, 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * زيارة جديدة لمريض موجود: يعيده لطابور الاستقبال بشكوى محدّثة دون تكرار
     * تسجيله. صور الزيارة (إن وُجدت) تُعالَج بالخلفية كما في store().
     */
    public function newVisit(StoreNewVisitRequest $request, $id)
    {
        try {
            $patient = $this->patientService->startNewVisit(
                $id,
                $request->validated(),
                [
                    'clinical_images' => $request->file('clinical_images'),
                    'x_ray_images' => $request->file('x_ray_images'),
                ]
            );

            return response_success(
                new PatientResource($patient),
                200,
                'New visit registered successfully, images are processing in background.'
            );
        } catch (ModelNotFoundException $e) {
            return response_error(null, 404, 'Patient not found.');
        } catch (\Exception $e) {
            $statusCode = is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600
                ? (int) $e->getCode()
                : 500;

            return response_error(null, $statusCode, $e->getMessage());
        }
    }

    public function stats()
    {
        try {
            $stats = $this->patientService->getDailyDashboardStats(auth()->id());

            return response_success($stats, 200, 'Daily stats fetched successfully.');
        } catch (\Exception $e) {
            return response_error(null, 500, $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpkRequest;
use App\Http\Resources\SpkResource;
use App\Services\SpkService;
use Illuminate\Http\Request;

class SpkController extends Controller
{
    protected $spkService;

    public function __construct(SpkService $spkService)
    {
        $this->spkService = $spkService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $employeeId = $request->query('employee_id');

        if ($user && $user->role && $user->role->name === 'Karyawan' && $user->employee) {
            $employeeId = $user->employee->id;
        }

        $spks = $this->spkService->getAllSpks($employeeId);

        return SpkResource::collection($spks);
    }

    public function store(SpkRequest $request)
    {
        $spk = $this->spkService->createSpk($request->validated());

        return (new SpkResource($spk))->response()->setStatusCode(201);
    }

    public function show(string $id)
    {
        $spk = $this->spkService->getSpkById($id);

        return new SpkResource($spk);
    }

    public function update(SpkRequest $request, string $id)
    {
        $spk = $this->spkService->updateSpk($id, $request->validated());

        return new SpkResource($spk);
    }

    public function destroy(string $id)
    {
        $this->spkService->deleteSpk($id);

        return response()->json(['message' => 'SPK berhasil dihapus.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpkRequest;
use App\Http\Resources\SpkResource;
use App\Services\SpkPdfParser;
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

    /**
     * Baca PDF SPK dari klien lalu kembalikan datanya (belum disimpan).
     */
    public function parsePdf(Request $request, SpkPdfParser $parser)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $data = $parser->parseFile($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $warnings = [];
        if (! $data['spk_number']) {
            $warnings[] = 'Nomor SPK tidak terbaca.';
        }
        if ($data['spk_value'] <= 0) {
            $warnings[] = 'Nilai total tidak terbaca.';
        }
        if (! $data['start_date']) {
            $warnings[] = 'Tanggal pekerjaan tidak terbaca.';
        }

        return response()->json([
            'message' => 'PDF terbaca. Periksa dulu sebelum disimpan.',
            'data' => $data,
            'warnings' => $warnings,
        ]);
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

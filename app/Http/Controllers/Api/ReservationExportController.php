<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReservationExportRequest;
use App\Services\ReservationExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationExportController extends Controller
{
    public function excel(ReservationExportRequest $request, int $residence, ReservationExportService $exports): StreamedResponse
    {
        $data = $request->validated();
        $locale = $data['locale'] ?? 'fr';
        $reservations = $exports->reservations($residence, $data['year'], $data['event'], $data['presence'] ?? -1);

        return response()->streamDownload(
            fn () => $exports->writeExcel($reservations, $locale),
            $exports->filename($reservations, $data['year'], 'xlsx'),
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function pdf(ReservationExportRequest $request, int $residence, ReservationExportService $exports): StreamedResponse
    {
        $data = $request->validated();
        $locale = $data['locale'] ?? 'fr';
        $reservations = $exports->reservations($residence, $data['year'], $data['event'], $data['presence'] ?? -1);
        $pdf = $exports->pdf($reservations, $locale);

        return response()->streamDownload(
            fn () => print $pdf,
            $exports->filename($reservations, $data['year'], 'pdf'),
            ['Content-Type' => 'application/pdf']
        );
    }
}

<?php

namespace App\Services;

use App\Models\Reservation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReservationExportService
{
    public function reservations(int $residence, int $year, int $event, int $presence): Collection
    {
        return Reservation::with(['event', 'guest.delegation', 'room.pavilion.residence', 'host.user'])
            ->whereHas('room.pavilion', fn (Builder $query) => $query->where('residence_id', $residence))
            ->when($event !== -1, fn (Builder $query) => $query->where('event_id', $event))
            ->when($year !== -1, fn (Builder $query) => $query->where(fn (Builder $dates) => $dates
                ->whereYear('date_entree', $year)->orWhereYear('date_sortie', $year)))
            ->when($presence !== -1, fn (Builder $query) => $query->where('presence', $presence === 1))
            ->orderBy('date_entree')->get();
    }

    public function writeExcel(Collection $reservations, string $locale): void
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($locale === 'ar' ? 'الحجوزات' : 'reservations');
        $sheet->setRightToLeft($locale === 'ar');
        $sheet->fromArray($this->headers($locale), null, 'A1');
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '228B22']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $row = 2;
        foreach ($reservations as $reservation) {
            $sheet->fromArray($this->row($reservation, $locale), null, "A{$row}");
            $row++;
        }
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save('php://output');
        $spreadsheet->disconnectWorksheets();
    }

    public function pdf(Collection $reservations, string $locale): string
    {
        $rtl = $locale === 'ar';
        $headers = collect($this->headers($locale))->map(fn (string $header) => '<th>'.e($header).'</th>')->join('');
        $rows = $reservations->map(function (Reservation $reservation) use ($locale) {
            $cells = collect($this->row($reservation, $locale))->map(fn ($value) => '<td>'.e((string) $value).'</td>')->join('');

            return "<tr>{$cells}</tr>";
        })->join('');
        $title = $rtl ? 'قائمة الحجوزات' : 'Liste des réservations';
        $direction = $rtl ? 'rtl' : 'ltr';
        $alignment = $this->alignment($rtl);
        $html = <<<HTML
<!doctype html><html dir="{$direction}"><head><meta charset="UTF-8"><style>
@page { margin: 18px; } body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; direction: {$direction}; }
h1 { text-align: center; font-size: 16px; } table { width: 100%; border-collapse: collapse; }
th { background: #228b22; color: white; } th, td { border: 1px solid #bbb; padding: 5px; text-align: {$alignment}; }
tr:nth-child(even) { background: #f0f0f0; }
</style></head><body><h1>{$title}</h1><table><thead><tr>{$headers}</tr></thead><tbody>{$rows}</tbody></table></body></html>
HTML;
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(Collection $reservations, int $year, string $extension): string
    {
        $event = $reservations->first()?->event?->libelle ?? 'reservations';
        $safeEvent = preg_replace('/[^\pL\pN _-]+/u', '', $event) ?: 'reservations';
        $period = $year === -1 ? 'toutes' : (string) $year;

        return "reservation {$safeEvent} {$period}.{$extension}";
    }

    private function headers(string $locale): array
    {
        return $locale === 'ar'
            ? ['الضيف', 'الوفد', 'عدد الأشخاص', 'الإقامة', 'الغرفة', 'المستقبل', 'تاريخ الدخول', 'تاريخ الخروج', 'حضور الحفل الرسمي']
            : ['Invité', 'Délégation', 'Nbr personnes', 'Résidence', 'Chambre', 'Accueillant', "Date d'entrée", 'Date de sortie', 'Cérémonie officielle'];
    }

    private function row(Reservation $reservation, string $locale): array
    {
        $guest = $reservation->guest;
        $delegation = $guest->delegation;
        $host = $reservation->host?->user;

        return [
            trim($guest->prenom.' '.mb_strtoupper($guest->nom)),
            $delegation?->nom ?? '',
            $delegation?->nombre ?? 0,
            $reservation->room->pavilion->residence->libelle,
            $reservation->room->reference,
            $host ? trim($host->prenom.' '.$host->nom) : '',
            $reservation->date_entree->format('d/m/Y'),
            $reservation->date_sortie->format('d/m/Y'),
            $locale === 'ar' ? ($reservation->presence ? 'نعم' : 'لا') : ($reservation->presence ? 'Oui' : 'Non'),
        ];
    }

    private function alignment(bool $rtl): string
    {
        return $rtl ? 'right' : 'left';
    }
}

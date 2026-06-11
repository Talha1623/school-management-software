<?php

namespace App\Http\Controllers;

use App\Models\BalanceSheetSettlement;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountsSettlementController extends Controller
{
    private function resolveSchoolLogoUrl(?string $logoPath): ?string
    {
        $logoPath = trim((string) $logoPath);
        if ($logoPath === '') {
            return null;
        }

        if (str_starts_with($logoPath, 'http://') || str_starts_with($logoPath, 'https://')) {
            return $logoPath;
        }

        if (str_starts_with($logoPath, 'storage/')) {
            return asset($logoPath);
        }

        return asset('storage/' . ltrim($logoPath, '/'));
    }

    private function filteredSettlementsQuery(Request $request)
    {
        $query = BalanceSheetSettlement::query();

        if ($request->filled('date')) {
            $query->whereDate('settlement_date', $request->get('date'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $settlements = $this->filteredSettlementsQuery($request)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('accounting.parent-wallet.accounts-settlement', compact('settlements'));
    }

    public function print(Request $request)
    {
        $settlements = $this->filteredSettlementsQuery($request)->latest('id')->get();

        $settings = GeneralSetting::getSettings();
        $schoolName = trim((string) ($settings->school_name ?? $settings->system_name ?? config('app.name', 'School Management System')));
        $schoolEmail = trim((string) ($settings->school_email ?? ''));
        $schoolAddress = trim((string) ($settings->address ?? ''));
        $schoolPhone = trim((string) ($settings->school_phone ?? ''));
        $printNote = trim((string) ($settings->accounts_settlement_print_note ?? $settings->fee_voucher_notice ?? ''));
        $schoolLogoUrl = $this->resolveSchoolLogoUrl($settings->logo ?? null);

        return view('accounting.parent-wallet.accounts-settlement-print', compact(
            'settlements',
            'schoolName',
            'schoolEmail',
            'schoolAddress',
            'schoolPhone',
            'printNote',
            'schoolLogoUrl'
        ));
    }

    public function export(Request $request, string $format)
    {
        $format = strtolower(trim($format));
        if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
            abort(404);
        }

        $settlements = $this->filteredSettlementsQuery($request)->latest('id')->get();
        $filenameDate = now()->format('Y-m-d_H-i');

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="accounts-settlement-' . $filenameDate . '.csv"',
            ];

            $callback = function () use ($settlements) {
                $stream = fopen('php://output', 'w');
                fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($stream, ['#', 'Date', 'Campus', 'User', 'Method', 'Transaction ID', 'Total Payment', 'Remarks']);

                foreach ($settlements as $index => $settlement) {
                    $user = $settlement->created_by_name ?: ($settlement->user_name === 'all' ? 'All' : $settlement->user_name);
                    if ($settlement->created_by_type) {
                        $user .= ' (' . $settlement->created_by_type . ')';
                    }

                    fputcsv($stream, [
                        $index + 1,
                        $settlement->settlement_date ? Carbon::parse($settlement->settlement_date)->format('d-m-Y') : '',
                        $settlement->campus === 'all' ? 'All' : ucfirst((string) $settlement->campus),
                        $user,
                        ucwords(str_replace('_', ' ', (string) $settlement->method)),
                        $settlement->transaction_id ?: '-',
                        number_format((float) $settlement->total_payment, 2, '.', ''),
                        $settlement->remarks ?: '-',
                    ]);
                }

                fclose($stream);
            };

            return response()->stream($callback, 200, $headers);
        }

        $rows = $settlements->map(function ($settlement, $index) {
            $user = $settlement->created_by_name ?: ($settlement->user_name === 'all' ? 'All' : $settlement->user_name);
            if ($settlement->created_by_type) {
                $user .= ' (' . $settlement->created_by_type . ')';
            }

            return [
                '#' => $index + 1,
                'Date' => $settlement->settlement_date ? Carbon::parse($settlement->settlement_date)->format('d-m-Y') : '',
                'Campus' => $settlement->campus === 'all' ? 'All' : ucfirst((string) $settlement->campus),
                'User' => $user,
                'Method' => ucwords(str_replace('_', ' ', (string) $settlement->method)),
                'Transaction ID' => $settlement->transaction_id ?: '-',
                'Total Payment' => number_format((float) $settlement->total_payment, 2),
                'Remarks' => $settlement->remarks ?: '-',
            ];
        });

        $html = view('accounting.parent-wallet.accounts-settlement-export-excel', ['rows' => $rows])->render();

        if ($format === 'excel') {
            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="accounts-settlement-' . $filenameDate . '.xls"',
            ]);
        }

        $settings = GeneralSetting::getSettings();
        $schoolName = trim((string) ($settings->school_name ?? $settings->system_name ?? config('app.name', 'School Management System')));
        $schoolEmail = trim((string) ($settings->school_email ?? ''));
        $schoolAddress = trim((string) ($settings->address ?? ''));
        $schoolPhone = trim((string) ($settings->school_phone ?? ''));
        $printNote = trim((string) ($settings->accounts_settlement_print_note ?? $settings->fee_voucher_notice ?? ''));
        $schoolLogoUrl = $this->resolveSchoolLogoUrl($settings->logo ?? null);

        $pdf = Pdf::loadView('accounting.parent-wallet.accounts-settlement-pdf', [
            'settlements' => $settlements,
            'schoolName' => $schoolName,
            'schoolEmail' => $schoolEmail,
            'schoolAddress' => $schoolAddress,
            'schoolPhone' => $schoolPhone,
            'printNote' => $printNote,
            'schoolLogoUrl' => $schoolLogoUrl,
            'printedAt' => now()->format('d-m-Y H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('accounts-settlement-' . $filenameDate . '.pdf');
    }

    public function destroy(string $type, int $id)
    {
        if ($type !== 'balance-sheet') {
            return back()->with('error', 'Invalid settlement type selected.');
        }

        $settlement = BalanceSheetSettlement::findOrFail($id);

        if (!empty($settlement->receipt_path) && Storage::disk('public')->exists($settlement->receipt_path)) {
            Storage::disk('public')->delete($settlement->receipt_path);
        }

        $settlement->delete();

        return back()->with('success', 'Settlement deleted successfully.');
    }
}

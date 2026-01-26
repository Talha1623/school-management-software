<?php

namespace App\Http\Controllers;

use App\Models\OnlineRejectedPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineRejectedPaymentController extends Controller
{
    /**
     * Display online rejected payments.
     */
    public function index(Request $request): View
    {
        $query = OnlineRejectedPayment::with('student');

        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower, $search) {
                    $q->whereRaw('LOWER(payment_id) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(student_code) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(parent_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(status) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(remarks) LIKE ?', ["%{$searchLower}%"])
                        ->orWhere('id', 'like', "%{$search}%");
                });
            }
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('accounting.parent-wallet.online-rejected-payments', compact('payments'));
    }

    /**
     * Delete an online rejected payment.
     */
    public function destroy(OnlineRejectedPayment $payment): RedirectResponse
    {
        $payment->delete();

        return redirect()
            ->route('accounting.parent-wallet.online-rejected-payments')
            ->with('success', 'Rejected payment deleted successfully.');
    }

    /**
     * Export rejected payments to Excel, CSV, or PDF.
     */
    public function export(Request $request, string $format)
    {
        $query = OnlineRejectedPayment::with('student');

        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower, $search) {
                    $q->whereRaw('LOWER(payment_id) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(student_code) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(parent_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(status) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(remarks) LIKE ?', ["%{$searchLower}%"])
                        ->orWhere('id', 'like', "%{$search}%");
                });
            }
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        switch ($format) {
            case 'excel':
                return $this->exportExcel($payments);
            case 'csv':
                return $this->exportCSV($payments);
            case 'pdf':
                return $this->exportPDF($payments);
            default:
                return redirect()->route('accounting.parent-wallet.online-rejected-payments')
                    ->with('error', 'Invalid export format!');
        }
    }

    private function exportExcel($payments)
    {
        $filename = 'online_rejected_payments_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Payment ID', 'Student Code', 'Parent', 'Paid Amount', 'Expected Amount', 'Date', 'Status', 'Remarks']);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_id ?? $payment->id,
                    $payment->student_code ?? '',
                    $payment->parent_name ?? ($payment->student->father_name ?? ''),
                    $payment->paid_amount ?? 0,
                    $payment->expected_amount ?? 0,
                    $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '',
                    $payment->status ?? 'Rejected',
                    $payment->remarks ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportCSV($payments)
    {
        $filename = 'online_rejected_payments_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Payment ID', 'Student Code', 'Parent', 'Paid Amount', 'Expected Amount', 'Date', 'Status', 'Remarks']);

            foreach ($payments as $payment) {
                fputcsv($file, [
                    $payment->payment_id ?? $payment->id,
                    $payment->student_code ?? '',
                    $payment->parent_name ?? ($payment->student->father_name ?? ''),
                    $payment->paid_amount ?? 0,
                    $payment->expected_amount ?? 0,
                    $payment->payment_date ? $payment->payment_date->format('Y-m-d') : '',
                    $payment->status ?? 'Rejected',
                    $payment->remarks ?? '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPDF($payments)
    {
        $html = view('accounting.parent-wallet.online-rejected-payments-pdf', compact('payments'))->render();

        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

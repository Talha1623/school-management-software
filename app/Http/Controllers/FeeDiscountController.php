<?php

namespace App\Http\Controllers;

use App\Models\StudentPayment;
use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeDiscountController extends Controller
{
    /**
     * Display the fee discount report with filters.
     */
    public function index(Request $request): View
    {
        return view('reports.fee-discount', $this->buildReportData($request));
    }

    public function export(Request $request, string $format)
    {
        $data = $this->buildReportData($request);
        $rows = $data['discountRecords'];

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportSheet($rows, $format === 'excel');
        }

        if ($format === 'pdf') {
            $pdfData = array_merge($data, [
                'settings' => GeneralSetting::getSettings(),
                'printedAt' => now()->format('d M Y, h:i A'),
            ]);
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                $pdf = Pdf::loadView('reports.fee-discount-pdf', $pdfData);
                return $pdf->stream('fee_discount_' . now()->format('Ymd_His') . '.pdf');
            }
            return response()->view('reports.fee-discount-pdf', $pdfData);
        }

        return redirect()->route('reports.fee-discount')->with('error', 'Invalid export format.');
    }

    public function print(Request $request): View
    {
        return view('reports.fee-discount-print', array_merge($this->buildReportData($request), [
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
        ]));
    }

    private function exportSheet($rows, bool $excel): StreamedResponse
    {
        $filename = 'fee_discount_' . now()->format('Ymd_His') . ($excel ? '.xls' : '.csv');
        $headers = [
            'Content-Type' => $excel ? 'application/vnd.ms-excel' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        $callback = function () use ($rows, $excel): void {
            $file = fopen('php://output', 'w');
            if ($excel) {
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            }
            fputcsv($file, ['#', 'Date', 'Student Code', 'Student Name', 'Campus', 'Class', 'Section', 'Payment Title', 'Payment Amount', 'Discount', 'Method']);
            foreach ($rows as $i => $r) {
                fputcsv($file, [
                    $i + 1,
                    $r['payment_date'],
                    $r['student_code'],
                    $r['student_name'],
                    $r['campus'],
                    $r['class'],
                    $r['section'],
                    $r['payment_title'],
                    (float) $r['payment_amount'],
                    (float) $r['discount'],
                    $r['method'],
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function buildReportData(Request $request): array
    {
        $filterCampus = $request->get('filter_campus');
        $filterFromDate = $request->get('filter_from_date');
        $filterToDate = $request->get('filter_to_date');

        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        if ($campuses->isEmpty()) {
            $campuses = StudentPayment::whereNotNull('campus')->distinct()->pluck('campus')
                ->merge(ClassModel::whereNotNull('campus')->distinct()->pluck('campus'))
                ->merge(Section::whereNotNull('campus')->distinct()->pluck('campus'))
                ->unique()->sort()->values();
        }

        $payments = StudentPayment::where('discount', '>', 0)
            ->when($filterCampus, fn($q) => $q->where('campus', $filterCampus))
            ->when($filterFromDate, fn($q) => $q->whereDate('payment_date', '>=', $filterFromDate))
            ->when($filterToDate, fn($q) => $q->whereDate('payment_date', '<=', $filterToDate))
            ->orderBy('payment_date', 'desc')
            ->get();

        $discountRecords = $payments->map(function ($payment) {
            $student = Student::where('student_code', $payment->student_code)->first();
            return [
                'student_code' => $payment->student_code,
                'student_name' => $student ? $student->student_name : 'N/A',
                'campus' => $payment->campus ?? ($student ? $student->campus : 'N/A'),
                'class' => $student ? $student->class : 'N/A',
                'section' => $student ? $student->section : 'N/A',
                'payment_title' => $payment->payment_title,
                'payment_amount' => $payment->payment_amount,
                'discount' => $payment->discount,
                'payment_date' => $payment->payment_date,
                'method' => $payment->method,
            ];
        })->values();

        return compact('campuses', 'discountRecords', 'filterCampus', 'filterFromDate', 'filterToDate');
    }
}


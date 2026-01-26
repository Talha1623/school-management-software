<?php

namespace App\Http\Controllers;

use App\Models\DeletedFee;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeletedFeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = DeletedFee::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(student_code) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(parent_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(payment_title) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(deleted_by) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $deletedFees = $query->orderBy('deleted_at', 'desc')->paginate($perPage)->withQueryString();

        return view('accounting.parent-wallet.deleted-fees', compact('deletedFees'));
    }

    public function restore(DeletedFee $deletedFee): RedirectResponse
    {
        try {
            $originalData = $deletedFee->original_data ?? [];

            if (!empty($originalData)) {
                StudentPayment::create($originalData);
            } else {
                StudentPayment::create([
                    'campus' => $deletedFee->campus,
                    'student_code' => $deletedFee->student_code,
                    'payment_title' => $deletedFee->payment_title,
                    'payment_amount' => $deletedFee->payment_amount,
                    'discount' => $deletedFee->discount ?? 0,
                    'method' => $deletedFee->method ?? 'Cash',
                    'payment_date' => $deletedFee->payment_date ?? now(),
                    'sms_notification' => 'Yes',
                    'accountant' => $deletedFee->deleted_by,
                    'late_fee' => 0,
                ]);
            }

            $deletedFee->delete();

            return redirect()
                ->route('accounting.parent-wallet.deleted-fees')
                ->with('success', 'Fee restored successfully!');
        } catch (\Exception $e) {
            return redirect()
                ->route('accounting.parent-wallet.deleted-fees')
                ->with('error', 'Failed to restore fee: ' . $e->getMessage());
        }
    }
}

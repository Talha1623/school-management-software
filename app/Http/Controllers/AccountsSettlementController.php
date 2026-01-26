<?php

namespace App\Http\Controllers;

use App\Models\CustomPayment;
use App\Models\ManagementExpense;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AccountsSettlementController extends Controller
{
    public function index(Request $request): View
    {
        $items = collect();

        $studentPayments = StudentPayment::with('student')->get();
        foreach ($studentPayments as $payment) {
            $studentName = $payment->student->student_name ?? 'N/A';
            $userLabel = $payment->student_code
                ? "{$studentName} ({$payment->student_code})"
                : $studentName;
            $amount = (float) $payment->payment_amount;
            $items->push([
                'source_type' => 'student_payment',
                'source_id' => $payment->id,
                'user' => $userLabel,
                'type' => 'Income',
                'date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'income' => $amount,
                'expense' => 0,
                'total' => $amount,
                'method' => $payment->method ?? 'N/A',
                'trx' => $payment->id,
                'remarks' => $payment->payment_title ?? 'N/A',
            ]);
        }

        $customPayments = CustomPayment::all();
        foreach ($customPayments as $payment) {
            $amount = (float) $payment->payment_amount;
            $items->push([
                'source_type' => 'custom_payment',
                'source_id' => $payment->id,
                'user' => $payment->accountant ?? 'N/A',
                'type' => 'Income',
                'date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'income' => $amount,
                'expense' => 0,
                'total' => $amount,
                'method' => $payment->method ?? 'N/A',
                'trx' => $payment->id,
                'remarks' => $payment->payment_title ?? 'N/A',
            ]);
        }

        $expenses = ManagementExpense::all();
        foreach ($expenses as $expense) {
            $amount = (float) $expense->amount;
            $items->push([
                'source_type' => 'management_expense',
                'source_id' => $expense->id,
                'user' => $expense->title ?? $expense->category ?? 'N/A',
                'type' => 'Expense',
                'date' => $expense->date ? $expense->date->format('Y-m-d') : null,
                'income' => 0,
                'expense' => $amount,
                'total' => 0 - $amount,
                'method' => $expense->method ?? 'N/A',
                'trx' => $expense->id,
                'remarks' => $expense->description ?? 'N/A',
            ]);
        }

        $items = $items->sortBy('date')->values();

        $balance = 0;
        $items = $items->map(function ($item) use (&$balance) {
            $balance += (float) $item['income'] - (float) $item['expense'];
            $item['balance'] = $balance;
            return $item;
        });

        if ($request->filled('search')) {
            $search = strtolower(trim($request->search));
            $items = $items->filter(function ($item) use ($search) {
                return str_contains(strtolower((string) $item['user']), $search)
                    || str_contains(strtolower((string) $item['type']), $search)
                    || str_contains(strtolower((string) $item['remarks']), $search)
                    || str_contains(strtolower((string) $item['method']), $search)
                    || str_contains(strtolower((string) $item['trx']), $search);
            })->values();
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('accounting.parent-wallet.accounts-settlement', [
            'items' => $paginatedItems,
        ]);
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        try {
            if ($type === 'student_payment') {
                StudentPayment::where('id', $id)->delete();
            } elseif ($type === 'custom_payment') {
                CustomPayment::where('id', $id)->delete();
            } elseif ($type === 'management_expense') {
                ManagementExpense::where('id', $id)->delete();
            }

            return redirect()
                ->route('accounting.parent-wallet.accounts-settlement')
                ->with('success', 'Record deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('accounting.parent-wallet.accounts-settlement')
                ->with('error', 'Failed to delete record: ' . $e->getMessage());
        }
    }
}

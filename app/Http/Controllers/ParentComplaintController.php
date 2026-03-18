<?php

namespace App\Http\Controllers;

use App\Models\ParentComplaint;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ParentComplaintController extends Controller
{
    /**
     * Show parent complaints list in admin panel.
     */
    public function index(Request $request): View
    {
        $query = ParentComplaint::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(parent_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereRaw('LOWER(subject) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(complain) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

        $complains = $query->latest()->paginate($perPage)->withQueryString();

        return view('parent-complain', compact('complains'));
    }

    /**
     * Store reply for a parent complaint.
     */
    public function reply(Request $request, $id)
    {
        $validated = $request->validate([
            'reply' => ['required', 'string', 'max:5000'],
            'sms_notification' => ['nullable', 'boolean'],
        ]);

        $complain = ParentComplaint::findOrFail($id);

        $complain->update([
            'reply' => $validated['reply'],
            'reply_date' => now(),
            'sms_notification' => $request->has('sms_notification') ? true : false,
            'status' => 'resolved',
        ]);

        // TODO: Send SMS notification if enabled
        if ($complain->sms_notification && $complain->phone) {
            // Add SMS sending logic here
        }

        return redirect()
            ->route('parent-complain')
            ->with('success', 'Reply sent successfully.');
    }
}


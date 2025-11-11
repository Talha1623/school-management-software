<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class FamilyVoucherController extends Controller
{
    /**
     * Show the family vouchers page with filters.
     */
    public function index(Request $request): View
    {
        // Group students by parent (using father_name or parent_id)
        $query = Student::select(
            DB::raw('COALESCE(father_name, "Unknown") as parent_name'),
            DB::raw('GROUP_CONCAT(DISTINCT student_name) as student_names'),
            DB::raw('GROUP_CONCAT(DISTINCT student_code) as student_codes'),
            DB::raw('GROUP_CONCAT(DISTINCT class) as classes'),
            DB::raw('GROUP_CONCAT(DISTINCT section) as sections'),
            DB::raw('MAX(campus) as campus'),
            DB::raw('COUNT(*) as student_count')
        )
        ->groupBy('father_name');
        
        // Apply filters
        if ($request->filled('campus')) {
            $query->where('campus', $request->campus);
        }
        
        // Type and vouchers_for are filter options, not stored in Student model
        // They will be used for voucher generation
        
        $families = $query->orderBy('parent_name')->paginate(20)->withQueryString();
        
        return view('accounting.fee-voucher.family', compact('families'));
    }
}


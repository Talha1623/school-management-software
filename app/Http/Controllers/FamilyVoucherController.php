<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Services\FeeVoucherBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class FamilyVoucherController extends Controller
{
    public function index(Request $request, FeeVoucherBuilder $builder, ?string $view = null, ?string $defaultCampus = null): View
    {
        $campuses = \App\Models\Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object) ['campus_name' => $campusName]);
            }
        }

        $filterCampus = $request->get('campus', $defaultCampus);

        $classes = $builder->classesForCampus($filterCampus);

        $sections = $request->filled('class')
            ? $builder->sectionsForCampusAndClass($filterCampus, (string) $request->class)
            : collect();

        if ($defaultCampus && $filterCampus) {
            $campuses = $campuses->filter(function ($campus) use ($filterCampus) {
                return ($campus->campus_name ?? $campus) === $filterCampus;
            })->values();
            if ($campuses->isEmpty()) {
                $campuses = collect([(object) ['campus_name' => $filterCampus]]);
            }
        }

        $families = new LengthAwarePaginator([], 0, 20);
        if ($request->hasAny(['campus', 'class', 'section', 'vouchers_for', 'type'])) {
            $families = $builder->listFamilies($request);
        }

        $view = $view ?? 'accounting.fee-voucher.family';

        return view($view, compact('campuses', 'classes', 'sections', 'filterCampus', 'families'));
    }

    public function print(Request $request, FeeVoucherBuilder $builder): View
    {
        return view('accounting.fee-voucher.print', $builder->buildFamilyVouchers($request));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Services\FeeVoucherBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentVoucherController extends Controller
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

        $context = $builder->resolveContext($request, false);
        $students = $builder->buildStudentFilterQuery($request, $context)
            ->orderBy('student_name')
            ->paginate(20)
            ->withQueryString();

        $view = $view ?? 'accounting.fee-voucher.student';

        return view($view, compact('students', 'classes', 'sections', 'campuses', 'filterCampus'));
    }

    public function getClassesByCampus(Request $request, FeeVoucherBuilder $builder): JsonResponse
    {
        $campus = trim((string) $request->get('campus', ''));

        if ($campus === '') {
            return response()->json(['classes' => []]);
        }

        return response()->json([
            'classes' => $builder->classNamesForCampus($campus),
        ]);
    }

    public function getSectionsByClass(Request $request, FeeVoucherBuilder $builder): JsonResponse
    {
        $className = trim((string) $request->get('class', ''));
        $campus = trim((string) $request->get('campus', ''));

        if ($className === '') {
            return response()->json(['sections' => []]);
        }

        $sections = $builder->sectionsForCampusAndClass($campus !== '' ? $campus : null, $className)
            ->map(fn ($section) => [
                'id' => $section->id ?? null,
                'name' => $section->name,
            ])
            ->values();

        return response()->json(['sections' => $sections]);
    }

    public function print(Request $request, FeeVoucherBuilder $builder): View
    {
        return view('accounting.fee-voucher.print', $builder->buildStudentVouchers($request));
    }
}

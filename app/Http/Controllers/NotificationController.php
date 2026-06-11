<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Staff;
use App\Services\MobilePushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        private readonly MobilePushNotificationService $pushNotifications,
    ) {
    }

    /**
     * Display the notification to parent page.
     */
    public function parent(Request $request): View
    {
        $campuses = $this->campusOptions();

        return view('notification.parent', compact('campuses'));
    }

    /**
     * Display the notification to staff page.
     */
    public function staff(Request $request): View
    {
        $campuses = $this->campusOptions(includeStaff: true);

        return view('notification.staff', compact('campuses'));
    }

    /**
     * Display the notification to student page.
     */
    public function student(Request $request): View
    {
        $campuses = $this->campusOptions();
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        $sections = collect();

        return view('notification.student', compact('campuses', 'classes', 'sections'));
    }

    public function sendStudent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'class' => ['nullable', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $stats = $this->pushNotifications->sendToStudents($validated, $validated['title'], $validated['body']);

        return redirect()
            ->route('notification.student')
            ->with('success', $this->resultMessage('students', $stats));
    }

    public function sendParent(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $stats = $this->pushNotifications->sendToParents($validated, $validated['title'], $validated['body']);

        return redirect()
            ->route('notification.parent')
            ->with('success', $this->resultMessage('parents', $stats));
    }

    public function sendStaff(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $stats = $this->pushNotifications->sendToStaff($validated, $validated['title'], $validated['body']);

        return redirect()
            ->route('notification.staff')
            ->with('success', $this->resultMessage('staff', $stats));
    }

    /**
     * Display the notification history page.
     */
    public function history(Request $request): View
    {
        $search = $request->get('search');
        $notificationHistory = collect();
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $currentPage = $request->get('page', 1);
        $items = $notificationHistory->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $notificationHistory->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('notification.history', compact('paginator', 'search'));
    }

    private function campusOptions(bool $includeStaff = false): \Illuminate\Support\Collection
    {
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections);

        if ($includeStaff) {
            $campusesFromStaff = Staff::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campuses->merge($campusesFromStaff);
        }

        $campuses = $campuses->unique()->sort()->values();

        if ($campuses->isEmpty()) {
            return collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        return $campuses;
    }

    /** @param array{recipients: int, in_app: int, push_sent: int, push_failed: int, no_tokens: int} $stats */
    private function resultMessage(string $audience, array $stats): string
    {
        if ($stats['recipients'] === 0) {
            return "No {$audience} matched your filters. Notification was not sent.";
        }

        return sprintf(
            'Notification sent to %d %s. In-app saved: %d. Push delivered: %d, failed: %d, no device token: %d.',
            $stats['recipients'],
            $audience,
            $stats['in_app'],
            $stats['push_sent'],
            $stats['push_failed'],
            $stats['no_tokens']
        );
    }
}

<?php

namespace App\Support;

use App\Models\AdminRole;
use Illuminate\Support\Facades\Auth;

class SchoolAdminAuth
{
    /**
     * True when logged in via admin / web back-office guards (ICMS panel).
     * Does not treat an active accountant-portal session as back-office.
     */
    public static function isBackOfficeGuard(): bool
    {
        if (Auth::guard('accountant')->check()) {
            return false;
        }

        if (Auth::guard('admin')->check()) {
            return true;
        }

        $webUser = Auth::guard('web')->user();
        if ($webUser instanceof AdminRole) {
            return true;
        }

        $defaultUser = Auth::user();
        if ($defaultUser instanceof AdminRole) {
            return true;
        }

        return false;
    }

    /**
     * True for any school back-office context (including dual-role email on accountant guard
     * when not currently in an accountant-portal session).
     */
    public static function check(): bool
    {
        if (self::isBackOfficeGuard()) {
            return true;
        }

        if (Auth::guard('accountant')->check()) {
            $email = Auth::guard('accountant')->user()->email ?? null;
            if ($email && AdminRole::where('email', $email)->exists()) {
                return true;
            }
        }

        return false;
    }
}

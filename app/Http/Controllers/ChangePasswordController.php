<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    /**
     * Show the change password form.
     */
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();
        
        return view('change-password', compact('admin'));
    }

    /**
     * Update the admin's password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6', 'confirmed'],
        ], [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.min' => 'New password must be at least 6 characters.',
            'new_password.confirmed' => 'New password confirmation does not match.',
        ]);

        $admin = Auth::guard('admin')->user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ])->withInput();
        }

        // Update password
        // Use DB::table to bypass model's setPasswordAttribute (which hashes again)
        DB::table('admin_roles')
            ->where('id', $admin->id)
            ->update([
                'password' => Hash::make($request->new_password),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Password changed successfully!');
    }
}


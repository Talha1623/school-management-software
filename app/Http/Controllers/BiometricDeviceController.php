<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BiometricDeviceController extends Controller
{
    /**
     * Display biometric device connection information.
     */
    public function index(Request $request): View
    {
        return view('biometric.devices');
    }

    /**
     * Store a newly created biometric device.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'device_serial_number' => ['nullable', 'string', 'max:255'],
            'device_ip_address' => ['nullable', 'ip'],
            'device_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'device_password' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Maintenance,Disconnected'],
            'connection_type' => ['required', 'in:Ethernet,WiFi,USB'],
            'last_sync_date' => ['nullable', 'date'],
            'total_users' => ['nullable', 'integer', 'min:0'],
            'total_fingerprints' => ['nullable', 'integer', 'min:0'],
            'firmware_version' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        BiometricDevice::create($validated);

        return redirect()
            ->route('biometric-device.manage')
            ->with('success', 'Biometric device added successfully!');
    }

    /**
     * Update the specified biometric device.
     */
    public function update(Request $request, BiometricDevice $biometricDevice): RedirectResponse
    {
        $validated = $request->validate([
            'device_name' => ['required', 'string', 'max:255'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'device_serial_number' => ['nullable', 'string', 'max:255'],
            'device_ip_address' => ['nullable', 'ip'],
            'device_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'device_password' => ['nullable', 'string', 'max:255'],
            'campus' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Active,Inactive,Maintenance,Disconnected'],
            'connection_type' => ['required', 'in:Ethernet,WiFi,USB'],
            'last_sync_date' => ['nullable', 'date'],
            'total_users' => ['nullable', 'integer', 'min:0'],
            'total_fingerprints' => ['nullable', 'integer', 'min:0'],
            'firmware_version' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $biometricDevice->update($validated);

        return redirect()
            ->route('biometric-device.manage')
            ->with('success', 'Biometric device updated successfully!');
    }

    /**
     * Remove the specified biometric device.
     */
    public function destroy(BiometricDevice $biometricDevice): RedirectResponse
    {
        $biometricDevice->delete();

        return redirect()
            ->route('biometric-device.manage')
            ->with('success', 'Biometric device deleted successfully!');
    }
}

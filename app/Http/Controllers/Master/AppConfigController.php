<?php

namespace App\Http\Controllers\Master;

use App\Domain\Settings\Models\Setting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppConfigController extends Controller
{
    /**
     * Show the app configuration form.
     */
    public function edit()
    {
        return inertia('Master/AppConfig/Edit', [
            'appName' => Setting::get('app_name', config('app.name')),
            'appLogo' => Setting::get('app_logo'),
            'maxUploadSize' => \App\Enums\FileUploadModule::APP_CONFIG->maxSize(),
        ]);
    }

    /**
     * Update the app configuration.
     */
    public function update(Request $request)
    {
        $maxSize = \App\Enums\FileUploadModule::APP_CONFIG->maxSize();

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:255'],
            'app_logo' => ['nullable', 'image', 'max:' . $maxSize],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'app_name'],
            ['value' => $validated['app_name']]
        );

        if ($request->boolean('remove_logo')) {
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
                Setting::where('key', 'app_logo')->delete();
            }
        } elseif ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            $oldLogo = Setting::get('app_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $extension = $request->file('app_logo')->getClientOriginalExtension();
            $fileName = now()->format('Y-m-d_H-i-s') . '_applogo.' . $extension;
            $path = $request->file('app_logo')->storeAs('app-logo', $fileName, 'public');
            
            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $path]
            );
        }

        return redirect()->back()->with('success', 'App configuration updated successfully.');
    }
}

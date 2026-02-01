<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\UpdateSiteSettingRequest;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SiteSettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::first() ?? new SiteSetting();

        return Inertia::render('Setting/Index', [
            'settings' => $settings
        ]);
    }

    public function update(UpdateSiteSettingRequest $request)
    {
        $settings = SiteSetting::first();
        if (!$settings) {
            $settings = new SiteSetting();
        }

        $data = $request->validated();

        if ($request->hasFile('logo_left')) {
            if ($settings->logo_left) Storage::disk('public')->delete($settings->logo_left);
            $data['logo_left'] = $request->file('logo_left')->store('settings', 'public');
        }

        if ($request->hasFile('logo_right')) {
            if ($settings->logo_right) Storage::disk('public')->delete($settings->logo_right);
            $data['logo_right'] = $request->file('logo_right')->store('settings', 'public');
        }

        $settings->fill($data);
        $settings->save();

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Settings', [
            'settingsForm' => [
                'language_switcher_enabled' => Setting::getValue('language_switcher_enabled', '1') === '1',
                'whatsapp_number' => Setting::getValue('whatsapp_number', ''),
            ],
        ]);
    }

    public function toggleLanguage(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'language_switcher_enabled'],
            [
                'value'     => $data['enabled'] ? '1' : '0',
                'type'      => 'boolean',
                'is_public' => true,
            ]
        );

        return back()->with('success', 'Language switcher setting updated.');
    }

    public function updateWhatsapp(Request $request)
    {
        $data = $request->validate([
            'whatsapp_number' => ['required', 'string', 'max:16', 'regex:/^\\+[1-9]\\d{6,14}$/'],
        ]);

        Setting::updateOrCreate(
            ['key' => 'whatsapp_number'],
            [
                'value' => trim($data['whatsapp_number']),
                'type' => 'string',
                'is_public' => true,
            ]
        );

        return back()->with('success', 'WhatsApp order number updated.');
    }
}

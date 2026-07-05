<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithToast;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PreferenceController extends Controller
{
    use InteractsWithToast;

    public function saveLayout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'layout_mode' => ['required', Rule::in(['regular', 'tablet'])],
        ]);

        $user = Auth::user();
        $settings = $user->settings ?? [];
        $settings['layout_mode'] = $data['layout_mode'];
        $user->forceFill(['settings' => $settings])->save();

        return redirect()->back();
    }

    public function display(): Response
    {
        $user = Auth::user();
        $display = data_get($user?->settings, 'display', []);

        return Inertia::render('Preferences/Display', [
            'scale' => data_get($display, 'scale', 'md'),
            'textBody' => data_get($display, 'text_body'),
            'textMuted' => data_get($display, 'text_muted'),
            'highContrast' => (bool) data_get($display, 'high_contrast', false),
        ]);
    }

    public function saveDisplay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scale' => ['required', Rule::in(['sm', 'md', 'lg', 'xl'])],
            'textBody' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'textMuted' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'highContrast' => ['boolean'],
        ]);

        $user = Auth::user();
        $settings = $user->settings ?? [];

        $highContrast = (bool) ($data['highContrast'] ?? false);

        $settings['display'] = [
            'scale' => $data['scale'],
            'text_body' => $highContrast ? null : ($data['textBody'] ?: null),
            'text_muted' => $highContrast ? null : ($data['textMuted'] ?: null),
            'high_contrast' => $highContrast,
        ];

        $user->forceFill(['settings' => $settings])->save();

        $this->toastSuccess(__('preferences.saved'));

        return redirect()->back();
    }
}

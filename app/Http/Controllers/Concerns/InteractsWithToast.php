<?php

namespace App\Http\Controllers\Concerns;

/**
 * Flash a toast for the next Inertia response. Consumed by AppLayout.vue which
 * bridges `flash.toast` into Nuxt UI's useToast(). Mirrors App\Traits\UiToast.
 */
trait InteractsWithToast
{
    protected function toast(string $type, string $title, ?string $description = null): void
    {
        session()->flash('ui.toast', [
            'type' => $type,
            'title' => $title,
            'description' => $description,
        ]);
    }

    protected function toastSuccess(string $title, ?string $description = null): void
    {
        $this->toast('success', $title, $description);
    }

    protected function toastError(string $title, ?string $description = null): void
    {
        $this->toast('error', $title, $description);
    }

    protected function toastWarning(string $title, ?string $description = null): void
    {
        $this->toast('warning', $title, $description);
    }

    protected function toastInfo(string $title, ?string $description = null): void
    {
        $this->toast('info', $title, $description);
    }
}

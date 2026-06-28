<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Badge extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $value = null,
        public ?string $icon = null,
        public ?string $iconRight = null,

    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                @php
                    $classes = ['badge badge-sm font-medium gap-1.5 rounded-full px-2.5 py-1.5'];
                    $attrClass = $this->attributes->get('class', '');
                    $hasVariant = preg_match('/\bbadge-(primary|secondary|accent|info|success|warning|error|ghost|neutral|soft)\b/', $attrClass);
                    if (! $hasVariant) {
                        $classes[] = 'badge-soft badge-neutral';
                    }
                @endphp
                <div {{ $attributes->class($classes) }}>
                    <!-- ICON -->
                    @if($icon)
                        <x-ui-icon :name="$icon" class="h-4 w-4" />
                    @endif

                    <!-- VALUE / SLOT -->
                    {{ $value ??  $slot }}
                    
                    <!-- ICON RIGHT -->
                    @if($iconRight)
                        <x-ui-icon :name="$iconRight" class="h-4 w-4" />
                    @endif
                </div>
            HTML;
    }
}

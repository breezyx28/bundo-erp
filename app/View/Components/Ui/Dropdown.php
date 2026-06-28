<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Dropdown extends Component
{
    public string $uuid;

    public function __construct(
        public ?string $id = null,
        public ?string $label = null,
        public ?string $icon = 'o-chevron-down',
        public ?bool $right = false,
        public ?bool $top = false,
        public ?bool $scroll = false,
        public ?string $maxHeight = 'max-h-96',
        public mixed $trigger = null
    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
            <div
                @class([
                    'dropdown relative inline-flex',
                    'dropdown-end' => $right,
                    'dropdown-top' => $top,
                ])
            >
                @if($trigger)
                    {{ $trigger }}
                @else
                    <button
                        type="button"
                        class="dropdown-toggle btn"
                        aria-haspopup="menu"
                        aria-expanded="false"
                        {{ $attributes->whereDoesntStartWith('class') }}
                    >
                        {{ $label }}
                        <x-ui-icon :name="$icon" />
                    </button>
                @endif

                <ul
                    @class([
                        'dropdown-menu dropdown-open:opacity-100 hidden min-w-60 w-auto',
                        $maxHeight => $scroll,
                        'overflow-y-auto' => $scroll,
                    ])
                    role="menu"
                    aria-orientation="vertical"
                >
                    {{ $slot }}
                </ul>
            </div>
        HTML;
    }
}

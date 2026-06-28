<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $uuid;

    /**
     * @param ?string  $title  The title of the alert, displayed in bold.
     * @param ?string  $icon  The icon displayed at the beginning of the alert.
     * @param ?string  $description  A short description under the title.
     * @param ?bool  $shadow  Whether to apply a shadow effect to the alert.
     * @param ?bool  $dismissible  Whether the alert can be dismissed by the user.
     * @slot  mixed  $actions  Slots for actionable elements like buttons or links.
     */
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        public ?string $icon = null,
        public ?string $description = null,
        public ?bool $shadow = false,
        public ?bool $dismissible = false,

        // Slots
        public mixed $actions = null
    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                <div
                    wire:key="{{ $uuid }}"
                    {{ $attributes->whereDoesntStartWith('class') }}
                    {{ $attributes->class(['alert flex items-start gap-3', 'shadow-md' => $shadow])}}
                    x-data="{ show: true }" x-show="show"
                >
                    @if($icon)
                        <x-ui-icon :name="$icon" class="self-center" />
                    @endif

                    @if($title)
                        <div>
                            <div @class(["font-bold" => $description])>{{ $title }}</div>
                            <div class="text-xs">{{ $description }}</div>
                        </div>
                    @else
                        <span>{{ $slot }}</span>
                    @endif

                    <div class="flex items-center gap-3">
                        {{ $actions }}
                    </div>

                    @if($dismissible)
                        <x-ui-button icon="o-x-mark" @click="show = false" class="btn-xs btn-circle btn-ghost static self-start end-0" />
                    @endif
                </div>
            BLADE;
    }
}

<?php

namespace App\View\Components\Ui;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public string $uuid;

    public string $tooltipPosition = 'lg:tooltip-top';

    public function __construct(
        public ?string $id = null,
        public ?string $label = null,
        public ?string $icon = null,
        public ?string $iconRight = null,
        public ?string $spinner = null,
        public ?string $link = null,
        public ?bool $external = false,
        public ?bool $noWireNavigate = false,
        public ?bool $responsive = false,
        public ?string $badge = null,
        public ?string $badgeClasses = null,
        public ?string $tooltip = null,
        public ?string $tooltipLeft = null,
        public ?string $tooltipRight = null,
        public ?string $tooltipBottom = null,
    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
        $this->tooltip = $this->tooltip ?? $this->tooltipLeft ?? $this->tooltipRight ?? $this->tooltipBottom;
        $this->tooltipPosition = $this->tooltipLeft ? 'lg:tooltip-left' : ($this->tooltipRight ? 'lg:tooltip-right' : ($this->tooltipBottom ? 'lg:tooltip-bottom' : 'lg:tooltip-top'));
    }

    public function spinnerTarget(): ?string
    {
        if ($this->spinner == 1) {
            return $this->attributes->whereStartsWith('wire:click')->first();
        }

        return $this->spinner;
    }

    public function render(): View|Closure|string
    {
        return <<<'BLADE'
                @if($link)
                    <a href="{!! $link !!}"
                @else
                    <button
                @endif

                    wire:key="{{ $uuid }}"
                    {{ $attributes->whereDoesntStartWith('class')->merge(['type' => 'button']) }}
                    {{ $attributes->class(['btn inline-flex items-center justify-center gap-1.5 text-sm font-medium leading-none', "!inline-flex lg:tooltip $tooltipPosition" => $tooltip]) }}

                    @if($link && $external)
                        target="_blank"
                    @endif

                    @if($link && !$external && !$noWireNavigate)
                        wire:navigate
                    @endif

                    @if($tooltip)
                        data-tip="{{ $tooltip }}"
                    @endif

                    @if($spinner)
                        wire:target="{{ $spinnerTarget() }}"
                        wire:loading.attr="disabled"
                    @endif
                >

                    <!-- SPINNER LEFT -->
                    @if($spinner && !$iconRight)
                        <span wire:loading wire:target="{{ $spinnerTarget() }}" class="loading loading-spinner w-5 h-5"></span>
                    @endif

                    <!-- ICON -->
                    @if($icon)
                        <span class="inline-flex shrink-0 items-center justify-center" @if($spinner) wire:loading.class="hidden" wire:target="{{ $spinnerTarget() }}" @endif>
                            <x-ui-icon :name="$icon" class="size-4" />
                        </span>
                    @endif

                    <!-- LABEL / SLOT -->
                    @if($label)
                        <span @class(["hidden lg:block" => $responsive ])>
                            {{ $label }}
                        </span>
                        @if(strlen($badge ?? '') > 0)
                            <span class="badge badge-sm {{ $badgeClasses }}">{{ $badge }}</span>
                        @endif
                    @else
                        {{ $slot }}
                    @endif

                    <!-- ICON RIGHT -->
                    @if($iconRight)
                        <span class="inline-flex shrink-0 items-center justify-center" @if($spinner) wire:loading.class="hidden" wire:target="{{ $spinnerTarget() }}" @endif>
                            <x-ui-icon :name="$iconRight" class="size-4" />
                        </span>
                    @endif

                    <!-- SPINNER RIGHT -->
                    @if($spinner && $iconRight)
                        <span wire:loading wire:target="{{ $spinnerTarget() }}" class="loading loading-spinner w-5 h-5"></span>
                    @endif

                @if(!$link)
                    </button>
                @else
                    </a>
                @endif
            BLADE;
    }
}

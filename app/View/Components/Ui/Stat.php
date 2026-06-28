<?php

namespace App\View\Components\Ui;

use App\Support\TablerIcons;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Stat extends Component
{
    public string $uuid;

    public string $tooltipPosition = 'lg:tooltip-top';

    public string $tablerIcon;

    public function __construct(
        public ?string $id = null,
        public ?string $value = null,
        public ?string $icon = null,
        public ?string $color = '',
        public ?string $title = null,
        public ?string $description = null,
        public ?string $trend = null,
        public ?string $trendDirection = null,
        public ?string $tooltip = null,
        public ?string $tooltipLeft = null,
        public ?string $tooltipRight = null,
        public ?string $tooltipBottom = null,
    ) {
        $this->uuid = "ui" . md5(serialize($this)) . $id;
        $this->tooltip = $this->tooltip ?? $this->tooltipLeft ?? $this->tooltipRight ?? $this->tooltipBottom;
        $this->tooltipPosition = $this->tooltipLeft ? 'lg:tooltip-left' : ($this->tooltipRight ? 'lg:tooltip-right' : ($this->tooltipBottom ? 'lg:tooltip-bottom' : 'lg:tooltip-top'));
        $this->tablerIcon = $icon ? TablerIcons::resolve($icon) : 'icon-[tabler--chart-bar]';

        if ($this->trend && ! $this->trendDirection) {
            $this->trendDirection = str_contains($this->color, 'error') || str_contains($this->color, 'down')
                ? 'down'
                : 'up';
        }
    }

    public function trendClass(): string
    {
        return $this->trendDirection === 'down' ? 'text-error' : 'text-success';
    }

    public function trendIcon(): string
    {
        return $this->trendDirection === 'down' ? 'icon-[tabler--arrow-down]' : 'icon-[tabler--arrow-up]';
    }

    public function render(): View|Closure|string
    {
        return <<<'HTML'
                <div
                    {{ $attributes->class(["flex flex-1 flex-col gap-4 min-w-0", "lg:tooltip $tooltipPosition" => $tooltip]) }}

                    @if($tooltip)
                        data-tip="{{ $tooltip }}"
                    @endif
                >
                    <div class="text-base-content flex items-center gap-2">
                        @if($icon)
                            <div class="avatar avatar-placeholder">
                                <div class="bg-base-200 rounded-field size-9 flex items-center justify-center {{ $color }}">
                                    <span class="{{ $tablerIcon }} size-6"></span>
                                </div>
                            </div>
                        @endif
                        @if($title)
                            <h5 class="text-lg font-medium truncate">{{ $title }}</h5>
                        @endif
                    </div>
                    <div>
                        <div class="text-base-content text-xl font-semibold tabular-nums">{{ $value ?? $slot }}</div>
                        @if($trend || $description)
                            <div class="flex flex-wrap items-center gap-2 text-sm font-semibold">
                                @if($trend)
                                    <span class="{{ $trendClass() }} inline-flex items-center gap-1">
                                        <span class="{{ $trendIcon() }} size-4"></span>
                                        {{ $trend }}
                                    </span>
                                @endif
                                @if($description)
                                    <span class="text-base-content/50 font-medium">{{ $description }}</span>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            HTML;
    }
}

<?php

namespace App\Services\Search;

class SearchResult
{
    public function __construct(
        public string $title,
        public ?string $subtitle = null,
        public ?string $url = null,
        public ?string $icon = null,
        public ?string $group = null,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'url' => $this->url,
            'icon' => $this->icon,
            'group' => $this->group,
        ];
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Str;

class TablerIcons
{
    /** @var array<string, string> icon key => Tailwind Iconify class (static for build safelist) */
    private const CLASSES = [
        'home' => 'icon-[tabler--home]',
        'shopping-cart' => 'icon-[tabler--shopping-cart]',
        'shopping-bag' => 'icon-[tabler--shopping-bag]',
        'cube' => 'icon-[tabler--box]',
        'box' => 'icon-[tabler--box]',
        'tag' => 'icon-[tabler--tag]',
        'bookmark' => 'icon-[tabler--bookmark]',
        'archive-box' => 'icon-[tabler--archive]',
        'archive' => 'icon-[tabler--archive]',
        'arrow-path' => 'icon-[tabler--refresh]',
        'refresh' => 'icon-[tabler--refresh]',
        'arrow-trending-up' => 'icon-[tabler--trending-up]',
        'trending-up' => 'icon-[tabler--trending-up]',
        'arrow-trending-down' => 'icon-[tabler--trending-down]',
        'trending-down' => 'icon-[tabler--trending-down]',
        'arrow-down-tray' => 'icon-[tabler--download]',
        'download' => 'icon-[tabler--download]',
        'arrow-up-tray' => 'icon-[tabler--upload]',
        'upload' => 'icon-[tabler--upload]',
        'arrow-right' => 'icon-[tabler--arrow-right]',
        'arrow-up' => 'icon-[tabler--arrow-up]',
        'arrow-down' => 'icon-[tabler--arrow-down]',
        'arrows-right-left' => 'icon-[tabler--arrows-left-right]',
        'arrows-left-right' => 'icon-[tabler--arrows-left-right]',
        'arrow-uturn-left' => 'icon-[tabler--arrow-back-up]',
        'arrow-back-up' => 'icon-[tabler--arrow-back-up]',
        'arrow-down-on-square' => 'icon-[tabler--square-arrow-down]',
        'square-arrow-down' => 'icon-[tabler--square-arrow-down]',
        'arrow-right-end-on-rectangle' => 'icon-[tabler--login]',
        'arrow-right-on-rectangle' => 'icon-[tabler--logout]',
        'logout' => 'icon-[tabler--logout]',
        'login' => 'icon-[tabler--login]',
        'banknotes' => 'icon-[tabler--cash]',
        'cash' => 'icon-[tabler--cash]',
        'bell' => 'icon-[tabler--bell]',
        'building-storefront' => 'icon-[tabler--building-store]',
        'building-store' => 'icon-[tabler--building-store]',
        'building-office-2' => 'icon-[tabler--building]',
        'building' => 'icon-[tabler--building]',
        'chart-bar' => 'icon-[tabler--chart-bar]',
        'chat-bubble-left-right' => 'icon-[tabler--messages]',
        'messages' => 'icon-[tabler--messages]',
        'chat-bubble-bottom-center-text' => 'icon-[tabler--message-2]',
        'message-2' => 'icon-[tabler--message-2]',
        'check' => 'icon-[tabler--check]',
        'check-badge' => 'icon-[tabler--rosette-discount-check]',
        'rosette-discount-check' => 'icon-[tabler--rosette-discount-check]',
        'check-circle' => 'icon-[tabler--circle-check]',
        'circle-check' => 'icon-[tabler--circle-check]',
        'chevron-down' => 'icon-[tabler--chevron-down]',
        'chevron-up' => 'icon-[tabler--chevron-up]',
        'chevron-left' => 'icon-[tabler--chevron-left]',
        'chevron-right' => 'icon-[tabler--chevron-right]',
        'chevron-up-down' => 'icon-[tabler--chevrons-up-down]',
        'chevrons-up-down' => 'icon-[tabler--chevrons-up-down]',
        'circle-stack' => 'icon-[tabler--stack-2]',
        'stack-2' => 'icon-[tabler--stack-2]',
        'clock' => 'icon-[tabler--clock]',
        'credit-card' => 'icon-[tabler--credit-card]',
        'currency-dollar' => 'icon-[tabler--currency-dollar]',
        'document-text' => 'icon-[tabler--file-text]',
        'file-text' => 'icon-[tabler--file-text]',
        'document-arrow-down' => 'icon-[tabler--file-download]',
        'file-download' => 'icon-[tabler--file-download]',
        'envelope' => 'icon-[tabler--mail]',
        'mail' => 'icon-[tabler--mail]',
        'exclamation-triangle' => 'icon-[tabler--alert-triangle]',
        'alert-triangle' => 'icon-[tabler--alert-triangle]',
        'exclamation-circle' => 'icon-[tabler--alert-circle]',
        'alert-circle' => 'icon-[tabler--alert-circle]',
        'eye' => 'icon-[tabler--eye]',
        'fire' => 'icon-[tabler--flame]',
        'flame' => 'icon-[tabler--flame]',
        'information-circle' => 'icon-[tabler--info-circle]',
        'info-circle' => 'icon-[tabler--info-circle]',
        'lock-closed' => 'icon-[tabler--lock]',
        'lock' => 'icon-[tabler--lock]',
        'magnifying-glass' => 'icon-[tabler--search]',
        'search' => 'icon-[tabler--search]',
        'paper-airplane' => 'icon-[tabler--send]',
        'send' => 'icon-[tabler--send]',
        'paper-clip' => 'icon-[tabler--paperclip]',
        'paperclip' => 'icon-[tabler--paperclip]',
        'pencil' => 'icon-[tabler--pencil]',
        'pencil-square' => 'icon-[tabler--pencil]',
        'play' => 'icon-[tabler--player-play]',
        'player-play' => 'icon-[tabler--player-play]',
        'pause' => 'icon-[tabler--player-pause]',
        'player-pause' => 'icon-[tabler--player-pause]',
        'plus' => 'icon-[tabler--plus]',
        'presentation-chart-line' => 'icon-[tabler--presentation-analytics]',
        'presentation-analytics' => 'icon-[tabler--presentation-analytics]',
        'printer' => 'icon-[tabler--printer]',
        'server-stack' => 'icon-[tabler--server]',
        'server' => 'icon-[tabler--server]',
        'sparkles' => 'icon-[tabler--sparkles]',
        'cog-6-tooth' => 'icon-[tabler--settings]',
        'cog' => 'icon-[tabler--settings]',
        'settings' => 'icon-[tabler--settings]',
        'truck' => 'icon-[tabler--truck]',
        'squares-2x2' => 'icon-[tabler--layout-grid]',
        'layout-grid' => 'icon-[tabler--layout-grid]',
        'table-cells' => 'icon-[tabler--table]',
        'table' => 'icon-[tabler--table]',
        'trash' => 'icon-[tabler--trash]',
        'user' => 'icon-[tabler--user]',
        'user-group' => 'icon-[tabler--users-group]',
        'users-group' => 'icon-[tabler--users-group]',
        'users' => 'icon-[tabler--users]',
        'x-circle' => 'icon-[tabler--circle-x]',
        'circle-x' => 'icon-[tabler--circle-x]',
        'x-mark' => 'icon-[tabler--x]',
        'x' => 'icon-[tabler--x]',
        'adjustments-horizontal' => 'icon-[tabler--adjustments-horizontal]',
        'question-mark-circle' => 'icon-[tabler--help-circle]',
        'help-circle' => 'icon-[tabler--help-circle]',
        'eye' => 'icon-[tabler--eye]',
        'eye-slash' => 'icon-[tabler--eye-off]',
    ];

    public static function resolve(string $name): string
    {
        if (Str::contains($name, '.')) {
            $key = Str::replace('.', '-', $name);

            return self::CLASSES[$key] ?? 'icon-[tabler--point]';
        }

        $base = $name;

        if (preg_match('/^[osm]-(.+)$/', $name, $matches)) {
            $base = $matches[1];
        }

        return self::CLASSES[$base] ?? self::CLASSES[Str::replace('_', '-', $base)] ?? 'icon-[tabler--point]';
    }

    /** @return list<string> */
    public static function safelist(): array
    {
        return array_values(array_unique(self::CLASSES));
    }
}

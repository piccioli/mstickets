@props([
    'active' => false,
    'activeChildItems' => false,
    'activeIcon' => null,
    'badge' => null,
    'badgeColor' => null,
    'badgeTooltip' => null,
    'childItems' => [],
    'depth' => 0,
    'first' => false,
    'grouped' => false,
    'icon' => null,
    'last' => false,
    'shouldOpenUrlInNewTab' => false,
    'sidebarCollapsible' => true,
    'subGrouped' => false,
    'subNavigation' => false,
    'url',
])

@php
    $sidebarCollapsible = $sidebarCollapsible && filament()->isSidebarCollapsibleOnDesktop();

    // Personalizzazione Orchestrator (US menu CAI): una voce SENZA URL con figli è un sotto-menu ad
    // espansione (anche annidato a più livelli); Filament di serie mostra sempre i figli e non annida oltre
    // il primo livello. Vedi app/Filament/CLAUDE.md, sezione "Sotto-menu di navigazione".
    $visibleChildItems = collect($childItems)
        ->filter(fn ($childItem): bool => $childItem->isVisible())
        ->values();
    $isExpandable = blank($url) && $visibleChildItems->isNotEmpty();
    $isDeepActive = function ($item) use (&$isDeepActive): bool {
        foreach ($item->getChildItems() as $descendant) {
            if ($descendant->isActive() || $isDeepActive($descendant)) {
                return true;
            }
        }

        return false;
    };
    $startsOpen = $active || $activeChildItems || $visibleChildItems->contains(
        fn ($childItem): bool => $childItem->isActive() || $isDeepActive($childItem),
    );
@endphp

<li
    @if ($isExpandable)
        x-data="{ open: @js($startsOpen) }"
    @endif
    {{
        $attributes->class([
            'fi-sidebar-item',
            'fi-active' => $active,
            'fi-sidebar-item-has-active-child-items' => $activeChildItems,
            'fi-sidebar-item-has-url' => filled($url),
        ])
    }}
>
    <a
        {{ \Filament\Support\generate_href_html($url, $shouldOpenUrlInNewTab) }}
        @if ($active)
            aria-current="page"
        @endif
        @if ($isExpandable)
            role="button"
            tabindex="0"
            x-bind:aria-expanded="open.toString()"
            x-on:click.prevent="open = ! open"
            x-on:keydown.enter.prevent="open = ! open"
            x-on:keydown.space.prevent="open = ! open"
            style="cursor: pointer"
        @else
            x-on:click="window.matchMedia(`(max-width: 1024px)`).matches && $store.sidebar.close()"
        @endif
        @if ($sidebarCollapsible && (! $subNavigation))
            x-bind:aria-label="$store.sidebar.isOpen ? null : @js(trim(strip_tags($slot->toHtml())))"
            x-data="{ tooltip: false }"
            x-effect="
                tooltip = $store.sidebar.isOpen
                    ? false
                    : {
                          content: @js($slot->toHtml()),
                          placement: document.dir === 'rtl' ? 'left' : 'right',
                          theme: $store.theme,
                      }
            "
            x-tooltip.html="tooltip"
        @endif
        class="fi-sidebar-item-btn"
    >
        @if (filled($icon) && ((! $subGrouped) || ($sidebarCollapsible && (! $subNavigation))))
            {{
                \Filament\Support\generate_icon_html(($active && $activeIcon) ? $activeIcon : $icon, attributes: (new \Filament\Support\View\ComponentAttributeBag([
                    'x-show' => ($subGrouped && $sidebarCollapsible) ? '! $store.sidebar.isOpen' : false,
                ]))->class(['fi-sidebar-item-icon']), size: \Filament\Support\Enums\IconSize::Large)
            }}
        @endif

        @if ((blank($icon) && $grouped) || $subGrouped)
            <div
                @if (filled($icon) && $subGrouped && $sidebarCollapsible && (! $subNavigation))
                    x-show="$store.sidebar.isOpen"
                @endif
                class="fi-sidebar-item-grouped-border"
            >
                @if (! $first)
                    <div
                        class="fi-sidebar-item-grouped-border-part-not-first"
                    ></div>
                @endif

                @if (! $last)
                    <div
                        class="fi-sidebar-item-grouped-border-part-not-last"
                    ></div>
                @endif

                <div class="fi-sidebar-item-grouped-border-part"></div>
            </div>
        @endif

        <span
            @if ($sidebarCollapsible && (! $subNavigation))
                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"
            @endif
            class="fi-sidebar-item-label"
        >
            {{ $slot }}
        </span>

        @if ($isExpandable)
            <span
                @if ($sidebarCollapsible && (! $subNavigation))
                    x-show="$store.sidebar.isOpen"
                @endif
                style="margin-inline-start: auto; display: inline-flex"
            >
                <x-filament::icon
                    icon="heroicon-m-chevron-down"
                    style="width: 1.25rem; height: 1.25rem; transition: transform 150ms"
                    x-bind:style="open ? '' : 'transform: rotate(-90deg)'"
                />
            </span>
        @endif

        @if (filled($badge))
            <span
                @if ($sidebarCollapsible && (! $subNavigation))
                    x-show="$store.sidebar.isOpen"
                    x-transition:enter="fi-transition-enter"
                    x-transition:enter-start="fi-transition-enter-start"
                    x-transition:enter-end="fi-transition-enter-end"
                @endif
                class="fi-sidebar-item-badge-ctn"
            >
                <x-filament::badge
                    :color="$badgeColor"
                    :tooltip="$badgeTooltip"
                >
                    {{ $badge }}
                </x-filament::badge>
            </span>
        @endif
    </a>

    @if ($visibleChildItems->isNotEmpty() && ($isExpandable || $active || $activeChildItems))
        <ul
            class="fi-sidebar-sub-group-items"
            @if ($isExpandable)
                x-show="open"
                x-collapse
                x-cloak
            @endif
            @if ($depth > 0)
                style="padding-inline-start: 0.75rem"
            @endif
        >
            @foreach ($visibleChildItems as $childItem)
                @php
                    $isChildItemChildItemsActive = $childItem->isChildItemsActive() || $isDeepActive($childItem);
                    $isChildActive = (! $isChildItemChildItemsActive) && $childItem->isActive();
                    $childItemActiveIcon = $childItem->getActiveIcon();
                    $childItemBadge = $childItem->getBadge();
                    $childItemBadgeColor = $childItem->getBadgeColor($childItemBadge);
                    $childItemBadgeTooltip = $childItem->getBadgeTooltip($childItemBadge);
                    $childItemIcon = $childItem->getIcon();
                    $shouldChildItemOpenUrlInNewTab = $childItem->shouldOpenUrlInNewTab();
                    $childItemUrl = $childItem->getUrl();
                    $childItemExtraAttributes = $childItem->getExtraAttributeBag();
                @endphp

                <x-filament-panels::sidebar.item
                    :active="$isChildActive"
                    :active-child-items="$isChildItemChildItemsActive"
                    :active-icon="$childItemActiveIcon"
                    :badge="$childItemBadge"
                    :badge-color="$childItemBadgeColor"
                    :badge-tooltip="$childItemBadgeTooltip"
                    :child-items="$childItem->getChildItems()"
                    :depth="$depth + 1"
                    :first="$loop->first"
                    grouped
                    :icon="$childItemIcon"
                    :last="$loop->last"
                    :should-open-url-in-new-tab="$shouldChildItemOpenUrlInNewTab"
                    sub-grouped
                    :sub-navigation="$subNavigation"
                    :url="$childItemUrl"
                    :attributes="\Filament\Support\prepare_inherited_attributes($childItemExtraAttributes)"
                >
                    {{ $childItem->getLabel() }}
                </x-filament-panels::sidebar.item>
            @endforeach
        </ul>
    @endif
</li>

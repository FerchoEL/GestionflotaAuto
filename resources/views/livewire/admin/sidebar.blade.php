<div class="erp-sidebar -mx-2">
    <style>
        .fi-main-sidebar .fi-sidebar-nav > .fi-sidebar-nav-tenant-menu-ctn,
        .fi-main-sidebar .fi-sidebar-nav > .fi-sidebar-nav-groups {
            display: none !important;
        }
    </style>

    <div class="space-y-2">
        @foreach ($modules as $module)
            <section
                x-data="{ open: {{ $module['active'] || $loop->first ? 'true' : 'false' }} }"
                class="overflow-hidden rounded-xl border border-white/10 bg-gray-900/95"
            >
                <button
                    type="button"
                    x-on:click="open = ! open"
                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition hover:bg-white/[0.03]"
                >
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary-500/12 text-primary-300 ring-1 ring-primary-400/15">
                        <x-filament::icon :icon="$module['icon']" class="h-5 w-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">
                            {{ $module['label'] }}
                        </p>
                        <p class="truncate text-sm font-semibold text-gray-100">
                            {{ $module['title'] }}
                        </p>
                    </div>

                    <x-filament::icon
                        icon="heroicon-o-chevron-down"
                        class="h-4 w-4 text-gray-500 transition duration-200"
                        x-bind:class="open ? 'rotate-180 text-gray-300' : ''"
                    />
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="border-t border-white/5"
                >
                    @if (filled($module['sections']))
                        <div class="px-3 py-3">
                            @foreach ($module['sections'] as $section)
                                <div @class(['pt-4' => ! $loop->first])>
                                    <div class="flex items-center gap-2 px-2">
                                        <x-filament::icon :icon="$section['icon']" class="h-4 w-4 text-gray-500" />
                                        <span class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            {{ $section['title'] }}
                                        </span>
                                    </div>

                                    <div class="mt-2 space-y-0.5">
                                        @foreach ($section['items'] as $item)
                                            <a
                                                href="{{ $item['url'] }}"
                                                wire:navigate
                                                @class([
                                                    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                                                    'bg-primary-500/12 text-primary-100 ring-1 ring-primary-400/15' => $item['active'],
                                                    'text-gray-300 hover:bg-white/[0.04] hover:text-white' => ! $item['active'],
                                                ])
                                            >
                                                <x-filament::icon
                                                    :icon="$item['icon']"
                                                    @class([
                                                        'h-4 w-4 flex-none transition',
                                                        'text-primary-300' => $item['active'],
                                                        'text-gray-500 group-hover:text-gray-300' => ! $item['active'],
                                                    ])
                                                />

                                                <span class="min-w-0 flex-1 truncate font-medium leading-5">
                                                    {{ $item['label'] }}
                                                </span>

                                                @if (filled($item['badge']))
                                                    <span
                                                        @class([
                                                            'inline-flex min-w-[1.4rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold',
                                                            'bg-danger-500/15 text-danger-300 ring-1 ring-danger-400/20' => ($item['badge']['color'] ?? null) === 'danger',
                                                            'bg-gray-500/15 text-gray-300 ring-1 ring-white/10' => ($item['badge']['color'] ?? null) !== 'danger',
                                                        ])
                                                    >
                                                        {{ $item['badge']['label'] }}
                                                    </span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-4 py-3">
                            <p class="text-xs text-gray-500">
                                {{ $module['description'] }}
                            </p>
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</div>

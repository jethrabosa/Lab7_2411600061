@php
    $navigationItems = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Products', 'route' => 'products.index', 'active' => 'products.*'],
        ['label' => 'Transactions', 'route' => 'transactions.index', 'active' => 'transactions.index|transactions.show'],
        ['label' => 'Stock Adjustment', 'route' => 'transactions.adjustment.create', 'active' => 'transactions.adjustment.*'],
        ['label' => 'Reports', 'route' => 'reports.index', 'active' => 'reports.*'],
        ['label' => 'Low Stock Alerts', 'route' => 'notifications.index', 'active' => 'notifications.*'],
    ];

    if (auth()->user()?->is_admin) {
        $navigationItems[] = ['label' => 'Suppliers', 'route' => 'suppliers.index', 'active' => 'suppliers.*'];
        $navigationItems[] = ['label' => 'Purchase Orders', 'route' => 'purchase-orders.index', 'active' => 'purchase-orders.*'];
    }

    $linkClasses = 'inline-flex min-h-10 items-center justify-center rounded-lg px-3.5 py-2 text-sm font-medium transition duration-150 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2';
@endphp

<nav x-data="{ open: false }" class="border-b border-slate-200/80 bg-white shadow-sm">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex min-h-[76px] items-center justify-between gap-4">
            <a href="{{ route('dashboard') }}" class="group inline-flex shrink-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <img
                    src="{{ asset('images/inventory-logo.jpg') }}"
                    alt="Ramon Magsaysay Memorial Colleges seal"
                    class="h-10 w-10 rounded-lg border border-slate-200 bg-white object-contain p-0.5 shadow-sm transition group-hover:shadow-md"
                >
                <span>
                    <span class="block text-sm font-bold tracking-tight text-slate-900 sm:text-base">Inventory Tracker</span>
                    <span class="hidden text-xs font-medium text-slate-500 sm:block">Stock &amp; procurement</span>
                </span>
            </a>

            <div class="hidden flex-wrap items-center justify-end gap-1.5 md:flex">
                @foreach ($navigationItems as $item)
                    @php($active = request()->routeIs(...explode('|', $item['active'])))
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($active) aria-current="page" @endif
                        class="{{ $linkClasses }} {{ $active ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 hover:bg-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            <button
                type="button"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="mobile-navigation"
                aria-label="Toggle navigation menu"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 lg:hidden"
            >
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 6 12 12M18 6 6 18" />
                </svg>
            </button>
        </div>

        <div id="mobile-navigation" x-show="open" x-cloak x-transition class="border-t border-slate-100 py-3 lg:hidden">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($navigationItems as $item)
                    @php($active = request()->routeIs(...explode('|', $item['active'])))
                    <a
                        href="{{ route($item['route']) }}"
                        @if ($active) aria-current="page" @endif
                        @click="open = false"
                        class="{{ $linkClasses }} {{ $active ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-200 hover:bg-indigo-700' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</nav>

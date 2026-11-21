<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Purchase Orders</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('suppliers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Manage Suppliers</a>
                <a href="{{ route('purchase-orders.create') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Create Purchase Order</a>
                <form method="POST" action="{{ route('purchase-orders.generate') }}">
                    @csrf
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Check Reorder Needs</button>
                </form>
            </div>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if(session('success'))<div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Order</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Supplier</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Items</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Expected delivery</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($orders as $order)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium"><a href="{{ route('purchase-orders.show', $order) }}" class="text-indigo-600 hover:underline">{{ $order->order_number }}</a></td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $order->supplier?->name ?? 'Archived supplier' }}</td>
                                <td class="px-5 py-4 text-sm"><span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-800">{{ str($order->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $order->items_count }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $order->expected_delivery_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-700">{{ $order->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">No purchase orders. Assign suppliers to products and select “Check Reorder Needs” to create orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>

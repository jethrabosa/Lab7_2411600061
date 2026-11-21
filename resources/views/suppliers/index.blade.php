<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Supplier Management</h2>
            <a href="{{ route('suppliers.create') }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Add Supplier</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Supplier</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Contact</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Lead time</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Orders</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">On time</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Accuracy</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Avg. delivery</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td class="px-5 py-4 text-sm font-medium text-gray-900">
                                    {{ $supplier->name }}
                                    <div class="text-xs font-normal text-gray-500">{{ $supplier->email }}</div>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $supplier->contact_name }}<div>{{ $supplier->phone }}</div></td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $supplier->lead_time_days }} days</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ $supplier->purchase_orders_count }} total / {{ $supplier->received_purchase_orders_count }} received</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ is_null($supplier->on_time_rate) ? '—' : $supplier->on_time_rate.'%' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ is_null($supplier->order_accuracy) ? '—' : $supplier->order_accuracy.'%' }}</td>
                                <td class="px-5 py-4 text-sm text-gray-600">{{ is_null($supplier->average_delivery_days) ? '—' : $supplier->average_delivery_days.' days' }}</td>
                                <td class="px-5 py-4 text-right text-sm">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="ml-3 inline" onsubmit="return confirm('Archive this supplier? Historical purchase orders will be kept.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline">Archive</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-5 py-8 text-center text-sm text-gray-500">No suppliers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500">Performance is calculated from completed purchase orders; accuracy is the share of order line items received in full.</p>
            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>

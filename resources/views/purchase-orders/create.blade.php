<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Create Purchase Order</h2>
                <p class="mt-1 text-sm text-gray-500">Choose a supplier and add quantities for its products.</p>
            </div>
            <a href="{{ route('purchase-orders.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Back to Orders</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                New purchase orders are submitted as <strong>Pending Approval</strong>. Product prices use the supplier price when available.
            </div>

            <form
                method="POST"
                action="{{ route('purchase-orders.store') }}"
                x-data="{ supplierId: '{{ old('supplier_id', '') }}' }"
                class="space-y-6 rounded-lg bg-white p-6 shadow-sm"
            >
                @csrf

                <div>
                    <label for="supplier_id" class="block text-sm font-medium text-gray-700">Supplier</label>
                    <select id="supplier_id" name="supplier_id" x-model="supplierId" required class="mt-1 block w-full rounded-md border-gray-300">
                        <option value="">Select a supplier</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->lead_time_days }} day lead time)</option>
                        @endforeach
                    </select>
                    @error('supplier_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    @if ($suppliers->isEmpty())
                        <p class="mt-2 text-sm text-amber-700">Add a supplier before creating a purchase order.</p>
                    @endif
                </div>

                @error('items')<p class="text-sm text-red-600">{{ $message }}</p>@enderror

                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">SKU</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Current stock</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Unit cost (PHP)</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Order quantity</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <tr x-show="supplierId === '{{ $product->supplier_id }}'">
                                    <td class="px-5 py-4 text-sm font-medium text-gray-900">{{ $product->name }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $product->sku }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">{{ $product->quantity }}</td>
                                    <td class="px-5 py-4 text-sm text-gray-600">₱{{ number_format((float) ($product->supplier_unit_cost ?? $product->unit_cost), 2) }}</td>
                                    <td class="px-5 py-3">
                                        <input
                                            type="number"
                                            name="items[{{ $product->id }}]"
                                            min="0"
                                            step="1"
                                            value="{{ old('items.'.$product->id, 0) }}"
                                            :disabled="supplierId !== '{{ $product->supplier_id }}'"
                                            aria-label="Order quantity for {{ $product->name }}"
                                            class="block w-32 rounded-md border-gray-300 text-sm"
                                        >
                                        @error('items.'.$product->id)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">No products are assigned to active suppliers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('purchase-orders.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" @disabled($suppliers->isEmpty() || $products->isEmpty()) class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">Submit for Approval</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $purchaseOrder->order_number }}</h2>
            <a href="{{ route('purchase-orders.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">Back to purchase orders</a>
        </div>
    </x-slot>
    <div class="py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))<div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>@endif
            @if($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <section class="grid gap-4 rounded-lg bg-white p-6 shadow-sm md:grid-cols-3">
                <div><div class="text-xs uppercase text-gray-500">Supplier</div><div class="font-medium">{{ $purchaseOrder->supplier?->name ?? 'Archived supplier' }}</div></div>
                <div><div class="text-xs uppercase text-gray-500">Status</div><div class="font-medium">{{ str($purchaseOrder->status)->replace('_', ' ')->title() }}</div></div>
                <div><div class="text-xs uppercase text-gray-500">Expected delivery</div><div class="font-medium">{{ $purchaseOrder->expected_delivery_at?->format('M d, Y') ?? 'Set when order is placed' }}</div></div>
                <div><div class="text-xs uppercase text-gray-500">Created by</div><div class="font-medium">{{ $purchaseOrder->creator?->name ?? 'Scheduled system' }}</div></div>
                <div><div class="text-xs uppercase text-gray-500">Approved by</div><div class="font-medium">{{ $purchaseOrder->approver?->name ?? '—' }}</div></div>
                <div><div class="text-xs uppercase text-gray-500">Order total</div><div class="font-medium">₱{{ number_format($purchaseOrder->total, 2) }}</div></div>
                @if($purchaseOrder->rejection_reason)
                    <div class="md:col-span-3"><div class="text-xs uppercase text-gray-500">Rejection reason</div><div>{{ $purchaseOrder->rejection_reason }}</div></div>
                @endif
            </section>

            <section class="overflow-x-auto rounded-lg bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Ordered</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Received</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Unit cost (PHP)</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Line total (PHP)</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-900">{{ $item->product->name }} <span class="text-gray-500">({{ $item->product->sku }})</span></td>
                                <td class="px-5 py-4 text-right text-sm">{{ $item->quantity }}</td>
                                <td class="px-5 py-4 text-right text-sm">{{ $item->received_quantity }}</td>
                                <td class="px-5 py-4 text-right text-sm">₱{{ number_format((float) $item->unit_cost, 2) }}</td>
                                <td class="px-5 py-4 text-right text-sm">₱{{ number_format($item->quantity * (float) $item->unit_cost, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            @if($purchaseOrder->status === 'pending_approval')
                <section class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-6 shadow-sm">
                    <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder) }}">@csrf<button class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Approve order</button></form>
                    <form method="POST" action="{{ route('purchase-orders.reject', $purchaseOrder) }}" class="flex flex-1 items-end gap-3">@csrf
                        <div class="min-w-52 flex-1"><label for="rejection_reason" class="block text-sm font-medium text-gray-700">Reason to reject</label><input id="rejection_reason" name="rejection_reason" required maxlength="1000" class="mt-1 block w-full rounded-md border-gray-300"></div>
                        <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white">Reject</button>
                    </form>
                </section>
            @elseif($purchaseOrder->status === 'approved')
                <form method="POST" action="{{ route('purchase-orders.mark-ordered', $purchaseOrder) }}" class="rounded-lg bg-white p-6 shadow-sm">@csrf
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Mark as placed with supplier</button>
                </form>
            @elseif(in_array($purchaseOrder->status, ['ordered', 'partially_received'], true))
                <form method="POST" action="{{ route('purchase-orders.receive', $purchaseOrder) }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">@csrf
                    <h3 class="font-semibold text-gray-900">Record delivery</h3>
                    <p class="text-sm text-gray-600">Enter the additional quantity received for this delivery. Stock updates are recorded as inventory transactions.</p>
                    @foreach($purchaseOrder->items as $item)
                        <div class="grid items-center gap-2 sm:grid-cols-3">
                            <label for="received-{{ $item->id }}" class="text-sm text-gray-700">{{ $item->product->name }} — {{ $item->quantity - $item->received_quantity }} outstanding</label>
                            <input id="received-{{ $item->id }}" name="received[{{ $item->id }}]" type="number" min="0" max="{{ $item->quantity - $item->received_quantity }}" value="0" class="rounded-md border-gray-300">
                        </div>
                    @endforeach
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Record received stock</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Product</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('products.update', $product) }}">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $product->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                            <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                            <input id="category" name="category" type="text" value="{{ old('category', $product->category) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="reorder_level" class="block text-sm font-medium text-gray-700">Reorder Level</label>
                            <input id="reorder_level" name="reorder_level" type="number" min="0" value="{{ old('reorder_level', $product->reorder_level) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="unit_cost" class="block text-sm font-medium text-gray-700">Unit Cost (PHP)</label>
                            <input id="unit_cost" name="unit_cost" type="number" min="0" step="0.01" value="{{ old('unit_cost', $product->unit_cost) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label for="supplier_id" class="block text-sm font-medium text-gray-700">Preferred Supplier</label>
                            <select id="supplier_id" name="supplier_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">No supplier assigned</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(old('supplier_id', $product->supplier_id) == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="supplier_unit_cost" class="block text-sm font-medium text-gray-700">Supplier Unit Price (PHP, optional)</label>
                            <input id="supplier_unit_cost" name="supplier_unit_cost" type="number" min="0" step="0.01" value="{{ old('supplier_unit_cost', $product->supplier_unit_cost) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="lead_time_days" class="block text-sm font-medium text-gray-700">Supplier Lead Time Override (days)</label>
                            <input id="lead_time_days" name="lead_time_days" type="number" min="0" value="{{ old('lead_time_days', $product->lead_time_days) }}" placeholder="Use supplier default" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('products.show', $product) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-semibold text-gray-700 hover:bg-gray-100">Cancel</a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $supplier->exists ? 'Edit Supplier' : 'Add Supplier' }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="space-y-6 rounded-lg bg-white p-6 shadow-sm">
                @csrf
                @if($supplier->exists) @method('PUT') @endif
                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Supplier name</label>
                        <input id="name" name="name" required maxlength="255" value="{{ old('name', $supplier->name) }}" class="mt-1 block w-full rounded-md border-gray-300">
                        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact_name" class="block text-sm font-medium text-gray-700">Contact name</label>
                        <input id="contact_name" name="contact_name" value="{{ old('contact_name', $supplier->contact_name) }}" class="mt-1 block w-full rounded-md border-gray-300">
                        @error('contact_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}" class="mt-1 block w-full rounded-md border-gray-300">
                        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                        <input id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" class="mt-1 block w-full rounded-md border-gray-300">
                        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="lead_time_days" class="block text-sm font-medium text-gray-700">Default lead time (days)</label>
                        <input id="lead_time_days" name="lead_time_days" type="number" min="0" max="3650" required value="{{ old('lead_time_days', $supplier->lead_time_days ?? 7) }}" class="mt-1 block w-full rounded-md border-gray-300">
                        @error('lead_time_days')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300">{{ old('address', $supplier->address) }}</textarea>
                        @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Pricing agreements / notes</label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300">{{ old('notes', $supplier->notes) }}</textarea>
                    @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('suppliers.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700">Cancel</a>
                    <button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">{{ $supplier->exists ? 'Save Supplier' : 'Create Supplier' }}</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

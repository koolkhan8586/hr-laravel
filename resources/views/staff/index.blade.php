    <x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- HEADER --}}
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Staff Management</h2>

        <div class="flex gap-2">
            <a href="{{ route('admin.staff.sample') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded text-sm">
                Download Sample
            </a>

            <a href="{{ route('admin.staff.export') }}"
   class="bg-indigo-600 text-white px-4 py-2 rounded text-sm">
    Export Staff
</a>


            <form action="{{ route('admin.staff.import') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="flex gap-2">
                @csrf
                <input type="file" name="file" required>
                <button class="bg-green-600 text-white px-4 py-2 rounded text-sm">
                    Import
                </button>
            </form>

            <a href="{{ route('admin.staff.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded text-sm">
                + Add Staff
            </a>
        </div>
    </div>

    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 mb-4 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- BULK DELETE --}}
    <form method="POST" action="{{ route('admin.staff.bulk.delete') }}">
        @csrf

        <button class="bg-red-700 text-white px-4 py-2 rounded mb-3">
            Bulk Delete
        </button>

        <table class="w-full bg-white shadow rounded text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3"><input type="checkbox" id="selectAll"></th>
                    <th class="p-3">Employee ID</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Department</th>
                    <th class="p-3">Designation</th>
                    <th class="p-3">Salary</th>
                    <th class="p-3">Status</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($staff as $item)
                <tr class="border-t">
                    <td class="p-3">
                        <input type="checkbox" name="staff_ids[]" value="{{ $item->id }}">
                    </td>
                    <td class="p-3">{{ $item->employee_id }}</td>
                    <td class="p-3">{{ $item->user->name }}</td>
                    <td class="p-3">{{ $item->department }}</td>
                    <td class="p-3">{{ $item->designation }}</td>
                    <td class="p-3 text-green-700">
                        Rs {{ number_format($item->salary,2) }}
                    </td>
                    <td class="p-3">
                        <form method="POST"
                              action="{{ route('admin.staff.toggle', $item->id) }}">
                            @csrf
                            <button class="px-2 py-1 rounded text-white
                            {{ $item->status == 'active' ? 'bg-green-500' : 'bg-gray-500' }}">
                                {{ ucfirst($item->status) }}
                            </button>
                        </form>
                    </td>
                    <td class="p-3 flex gap-2">
                        <a href="{{ route('admin.staff.edit', $item->id) }}"
                           class="bg-yellow-500 text-white px-3 py-1 rounded text-xs">
                            Edit
                        </a>
                        <form method="POST"
                              action="{{ route('admin.staff.destroy', $item->id) }}">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </form>

</div>
</x-app-layout>

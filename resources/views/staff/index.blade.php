<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Staff List</h2>

        <div class="flex items-center gap-3">

            {{-- Import Form --}}
            <form action="{{ route('staff.import') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="file"
                       class="border rounded px-2 py-1 text-sm"
                       required>
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                    Import Staff
                </button>
            </form>

            {{-- Add Staff --}}
            <a href="{{ route('staff.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">
                Add Staff
            </a>

        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border text-sm">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-3 border">Employee ID</th>
                <th class="p-3 border">Name</th>
                <th class="p-3 border">Email</th>
                <th class="p-3 border">Department</th>
                <th class="p-3 border">Designation</th>
                <th class="p-3 border">Salary</th>
                <th class="p-3 border text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $item)
                <tr class="hover:bg-gray-50">
                    <td class="p-3 border">{{ $item->employee_id }}</td>
                    <td class="p-3 border">{{ $item->user->name ?? '-' }}</td>
                    <td class="p-3 border">{{ $item->user->email ?? '-' }}</td>
                    <td class="p-3 border">{{ $item->department }}</td>
                    <td class="p-3 border">{{ $item->designation }}</td>
                    <td class="p-3 border">{{ number_format($item->salary, 2) }}</td>

                    <td class="p-3 border text-center space-x-2">

                        {{-- Edit --}}
                        <a href="{{ route('staff.edit', $item->id) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded shadow">
                            Edit
                        </a>

                        {{-- Reset Password --}}
                        <form action="{{ route('staff.reset', $item->id) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            <button type="submit"
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded shadow">
                                Reset
                            </button>
                        </form>

                        {{-- Delete --}}
                        <form action="{{ route('staff.destroy', $item->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Are you sure you want to delete this staff?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded shadow">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center p-4 text-gray-500">
                        No staff found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
</x-app-layout>

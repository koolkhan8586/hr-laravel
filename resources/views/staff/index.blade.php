<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Staff List</h2>

        <div class="flex gap-3">
            <a href="{{ route('staff.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded">
                Add Staff
            </a>
        </div>
    </div>

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Employee ID</th>
                <th class="p-2 border">Name</th>
                <th class="p-2 border">Email</th>
                <th class="p-2 border">Department</th>
                <th class="p-2 border">Designation</th>
                <th class="p-2 border">Salary</th>
                <th class="p-2 border">Action</th>
            </tr>
        </thead>

        <tbody>
            @foreach($staff as $item)
                <tr>
                    <td class="p-2 border">{{ $item->employee_id }}</td>
                    <td class="p-2 border">{{ $item->user->name }}</td>
                    <td class="p-2 border">{{ $item->user->email }}</td>
                    <td class="p-2 border">{{ $item->department }}</td>
                    <td class="p-2 border">{{ $item->designation }}</td>
                    <td class="p-2 border">{{ number_format($item->salary, 2) }}</td>

                    <td class="p-2 border space-x-2">

                        {{-- Edit --}}
                        <a href="{{ route('staff.edit', $item->id) }}"
                           class="bg-yellow-500 text-white px-3 py-1 rounded">
                            Edit
                        </a>

                        {{-- Reset Password --}}
                        <a href="{{ route('staff.reset.password', $item->id) }}"
                           class="bg-purple-600 text-white px-3 py-1 rounded">
                            Reset
                        </a>

                        {{-- Delete --}}
                        <form action="{{ route('staff.destroy', $item->id) }}"
                              method="POST"
                              class="inline"
                              onsubmit="return confirm('Are you sure you want to delete this staff?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-red-600 text-white px-3 py-1 rounded">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</div>
</x-app-layout>

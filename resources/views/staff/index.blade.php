<x-app-layout>
<div class="max-w-7xl mx-auto py-6 px-4">

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Staff List</h2>

        <div class="flex gap-3">

            {{-- Import Form --}}
            <form action="{{ route('staff.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" required
                    class="border rounded px-2 py-1 text-sm">

                <button type="submit"
                    class="bg-green-600 text-white px-3 py-2 rounded">
                    Import Staff
                </button>
            </form>

            {{-- Add Staff Button --}}
            <a href="{{ route('staff.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded">
                Add Staff
            </a>

        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <table class="w-full border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 border">Employee ID</th>
                <th class="p-2 border">Name</th>
                <th class="p-2 border">Email</th>
                <th class="p-2 border">Department</th>
                <th class="p-2 border">Designation</th>
                <th class="p-2 border">Salary</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $member)
                <tr>
                    <td class="p-2 border">{{ $member->employee_id }}</td>
                    <td class="p-2 border">{{ $member->user->name ?? '' }}</td>
                    <td class="p-2 border">{{ $member->user->email ?? '' }}</td>
                    <td class="p-2 border">{{ $member->department }}</td>
                    <td class="p-2 border">{{ $member->designation }}</td>
                    <td class="p-2 border">{{ $member->salary }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center p-4 text-gray-500">
                        No Staff Found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
</x-app-layout>

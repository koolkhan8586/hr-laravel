<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-6">

    {{-- ================= HEADER ================= --}}
    <div class="flex justify-between items-center mb-8">

        <h2 class="text-2xl font-bold text-gray-800">
            Staff Management
        </h2>

        <div class="flex items-center gap-3 flex-wrap">

            {{-- Export Staff --}}
            <a href="{{ route('admin.staff.export') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow text-sm">
                Export
            </a>

            {{-- Download Sample --}}
            <a href="{{ route('admin.staff.sample') }}"
               class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded shadow text-sm">
                Download Sample
            </a>

            {{-- Import Staff --}}
            <form action="{{ route('admin.staff.import') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="flex items-center gap-2">
                @csrf
                <input type="file"
                       name="file"
                       class="border rounded px-3 py-1 text-sm"
                       required>

                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow text-sm">
                    Import
                </button>
            </form>

            {{-- Add Staff --}}
            <a href="{{ route('admin.staff.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm">
                + Add Staff
            </a>

        </div>
    </div>


    {{-- ================= SUCCESS / ERROR MESSAGE ================= --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif


    {{-- ================= STAFF TABLE ================= --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="p-3 text-left">Employee ID</th>
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-left">Department</th>
                    <th class="p-3 text-left">Designation</th>
                    <th class="p-3 text-left">Salary</th>
                    <th class="p-3 text-center">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($staff as $item)
                    <tr class="border-t hover:bg-gray-50 transition">

                        <td class="p-3">
                            {{ $item->employee_id }}
                        </td>

                        <td class="p-3 font-medium">
                            {{ $item->user->name ?? '-' }}
                        </td>

                        <td class="p-3 text-gray-600">
                            {{ $item->user->email ?? '-' }}
                        </td>

                        <td class="p-3">
                            {{ $item->department }}
                        </td>

                        <td class="p-3">
                            {{ $item->designation }}
                        </td>

                        <td class="p-3 font-semibold text-green-700">
                            Rs {{ number_format($item->salary, 2) }}
                        </td>

                        {{-- ================= ACTION BUTTONS ================= --}}
                        <td class="p-3 text-center">

                            <div class="flex justify-center items-center gap-2 flex-wrap">

                                {{-- Edit --}}
                                <a href="{{ route('admin.staff.edit', $item->id) }}"
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-xs shadow">
                                    Edit
                                </a>

                                {{-- Reset Password --}}
                                <form action="{{ route('admin.staff.reset.password', $item->id) }}"
                                      method="POST"
                                      class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs shadow">
                                        Reset
                                    </button>
                                </form>

                                {{-- Delete --}}
                                <form action="{{ route('admin.staff.destroy', $item->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('Are you sure you want to delete this staff member?')"
                                      class="inline">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs shadow">
                                        Delete
                                    </button>
                                </form>

                            </div>

                        </td>

                    </tr>

                @empty
                    <tr>
                        <td colspan="7"
                            class="text-center p-6 text-gray-500">
                            No staff found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

</x-app-layout>

    <x-app-layout>

@php
    $sortLink = function ($key) use ($sort, $dir) {
        $next = ($sort === $key && $dir === 'asc') ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['sort' => $key, 'dir' => $next]);
    };
    $arrow = fn ($key) => $sort === $key ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
@endphp



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

    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
            {{ session('error') }}
        </div>
    @endif

    {{-- BULK ACTIONS --}}
    {{-- The form is kept empty and outside the table. The checkboxes and the
         buttons join it through the form attribute, so the per-row forms below
         are not nested inside it. --}}
    <form method="POST" action="{{ route('admin.staff.bulk.delete') }}" id="bulkForm">
        @csrf
    </form>

    <div class="flex flex-wrap gap-2 mb-3 items-center">
        <span class="text-sm text-gray-500">With selected:</span>

        <button form="bulkForm"
                formaction="{{ route('admin.staff.bulk.tracking') }}"
                name="tracks" value="0"
                class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded text-sm">
            Payroll Only
        </button>

        <button form="bulkForm"
                formaction="{{ route('admin.staff.bulk.tracking') }}"
                name="tracks" value="1"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded text-sm">
            Mark Attendance
        </button>

        <button form="bulkForm"
                onclick="return confirm('Delete the selected staff? This cannot be undone.')"
                class="bg-red-700 hover:bg-red-800 text-white px-4 py-2 rounded text-sm">
            Bulk Delete
        </button>
    </div>

    <p class="text-xs text-gray-500 mb-3">
        Payroll only means the employee is paid each month but never marks
        attendance, so they stay off the absence, late and leave reports.
    </p>

        <table class="w-full bg-white shadow rounded text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3"><input type="checkbox" id="selectAll"></th>
                    <th class="p-3"><a href="{{ $sortLink('code') }}" class="hover:underline">Employee ID{{ $arrow('code') }}</a></th>
                    <th class="p-3"><a href="{{ $sortLink('name') }}" class="hover:underline">Name{{ $arrow('name') }}</a></th>
                    <th class="p-3"><a href="{{ $sortLink('department') }}" class="hover:underline">Department{{ $arrow('department') }}</a></th>
                    <th class="p-3"><a href="{{ $sortLink('designation') }}" class="hover:underline">Designation{{ $arrow('designation') }}</a></th>
                    <th class="p-3"><a href="{{ $sortLink('salary') }}" class="hover:underline">Salary{{ $arrow('salary') }}</a></th>
                    <th class="p-3">Attendance</th>
                    <th class="p-3"><a href="{{ $sortLink('status') }}" class="hover:underline">Employment{{ $arrow('status') }}</a></th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($staff as $item)
                <tr class="border-t">
                    <td class="p-3">
                        <input type="checkbox" form="bulkForm" name="staff_ids[]" value="{{ $item->id }}">
                    </td>
                    <td class="p-3">{{ $item->employee_id }}</td>
                    <td class="p-3">{{ $item->user->name }}</td>
                    <td class="p-3">{{ $item->department }}</td>
                    <td class="p-3">{{ $item->designation }}</td>
                    <td class="p-3 text-green-700">
                        Rs {{ number_format($item->salary,2) }}
                    </td>
                    <td class="p-3">
                        @if($item->user && !$item->user->tracksAttendance())
                            <span class="bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs">
                                Payroll only
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                Tracked
                            </span>
                        @endif
                    </td>
                    <td class="p-3">
                        @if($item->status === 'active')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-semibold">
                                Active
                            </span>
                        @else
                            <span class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-xs font-semibold">
                                Left / Resigned
                            </span>
                        @endif
                        <form method="POST"
                              action="{{ route('admin.staff.toggle', $item->id) }}"
                              class="mt-1"
                              onsubmit="return confirm('{{ $item->status === 'active'
                                  ? 'Mark '.addslashes($item->user->name ?? 'this employee').' as left / resigned? They will be hidden from daily attendance reports.'
                                  : 'Reinstate '.addslashes($item->user->name ?? 'this employee').'? They will appear in daily attendance again.' }}');">
                            @csrf
                            <button class="px-2 py-1 rounded text-white text-xs
                            {{ $item->status == 'active' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-green-600 hover:bg-green-700' }}">
                                {{ $item->status == 'active' ? 'Mark Left' : 'Reinstate' }}
                            </button>
                        </form>
                    </td>
                    <td class="p-3 flex flex-wrap gap-2">
                        <a href="{{ route('admin.staff.edit', $item->id) }}"
                           class="bg-yellow-500 text-white px-3 py-1 rounded text-xs">
                            Edit
                        </a>
                        @if(filled($item->user?->mobile))
                        <form method="POST"
                              action="{{ route('admin.staff.reset.password', $item->id) }}"
                              onsubmit="return confirm('Reset password and send new credentials to {{ addslashes($item->user->name ?? 'this employee') }} on WhatsApp?');">
                            @csrf
                            <button class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">
                                Reset &amp; WhatsApp
                            </button>
                        </form>
                        @else
                        <span class="text-xs text-gray-400" title="Add a mobile number to send credentials via WhatsApp">
                            No mobile
                        </span>
                        @endif
                        <form method="POST"
                              action="{{ route('admin.staff.destroy', $item->id) }}">
                            @csrf
                            @method('DELETE')
                            <button onclick="return confirm('Delete {{ addslashes($item->user->name ?? 'this employee') }}?')"
                                    class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

</div>

<script>
    document.getElementById('selectAll')?.addEventListener('change', function () {
        document.querySelectorAll('input[name="staff_ids[]"]')
            .forEach(box => { box.checked = this.checked; });
    });
</script>
</x-app-layout>

<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4">

    <h2 class="text-2xl font-bold mb-6">Leave Management</h2>

    {{-- SUCCESS MESSAGE --}}
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- TABLE --}}
    <div class="bg-white shadow rounded overflow-hidden">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Days</th>
                    <th class="p-3 text-left">Applied On</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($leaves as $leave)
                <tr class="border-t">

                    <td class="p-3">
                        {{ $leave->user->name }}
                    </td>

                    <td class="p-3 capitalize">
                        {{ str_replace('_',' ', $leave->type) }}
                    </td>

                    <td class="p-3">
                        {{ $leave->calculated_days }}
                    </td>

                    {{-- NEW COLUMN --}}
                    <td class="p-3 text-gray-600">
                        {{ $leave->created_at->format('d M Y') }}
                    </td>

                    <td class="p-3">
                        @if($leave->status == 'pending')
                            <span class="text-yellow-600 font-semibold">Pending</span>
                        @elseif($leave->status == 'approved')
                            <span class="text-green-600 font-semibold">Approved</span>
                        @else
                            <span class="text-red-600 font-semibold">Rejected</span>
                        @endif
                    </td>

                    <td class="p-3 space-x-2">

                        {{-- APPROVE --}}
                        @if($leave->status == 'pending')
                        <form method="POST"
                              action="{{ route('admin.leave.approve', $leave->id) }}"
                              class="inline">
                            @csrf
                            <button class="bg-green-600 text-white px-3 py-1 rounded text-xs">
                                Approve
                            </button>
                        </form>

                        {{-- REJECT --}}
                        <form method="POST"
                              action="{{ route('admin.leave.reject', $leave->id) }}"
                              class="inline">
                            @csrf
                            <button class="bg-red-600 text-white px-3 py-1 rounded text-xs">
                                Reject
                            </button>
                        </form>
                        @endif

                        {{-- REVERT --}}
                        @if($leave->status == 'approved')
                        <form method="POST"
                              action="{{ route('admin.leave.revert', $leave->id) }}"
                              class="inline">
                            @csrf
                            <button class="bg-orange-500 text-white px-3 py-1 rounded text-xs">
                                Revert
                            </button>
                        </form>
                        @endif

                        {{-- DELETE --}}
                        <form method="POST"
                              action="{{ route('admin.leave.delete', $leave->id) }}"
                              class="inline"
                              onsubmit="return confirm('Delete this leave?')">
                            @csrf
                            @method('DELETE')
                            <button class="bg-gray-700 text-white px-3 py-1 rounded text-xs">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>

                @empty
                <tr>
                    <td colspan="6" class="p-4 text-center text-gray-500">
                        No Leave Requests Found
                    </td>
                </tr>
                @endforelse
            </tbody>

        </table>
    </div>

</div>

</x-app-layout>

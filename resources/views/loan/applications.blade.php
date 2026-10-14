<x-app-layout>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6">

    <div class="flex justify-between items-center mb-2" style="flex-wrap:wrap; gap:10px;">
        <h2 class="text-2xl font-bold text-gray-800">Loan Applications</h2>

        <div style="display:flex; gap:8px;">
            <a href="{{ route('admin.loan.applications.export', request()->only('status')) }}"
               class="text-white px-4 py-2 rounded shadow text-sm"
               style="background:#2563eb;">
                Export
            </a>

            <a href="{{ route('admin.loan.approvers.index') }}"
               class="text-white px-4 py-2 rounded shadow text-sm"
               style="background:#4f46e5;">
                Loan Approvers
            </a>

            <a href="{{ route('admin.loan.index') }}"
               class="text-white px-4 py-2 rounded shadow text-sm"
               style="background:#6b7280;">
                Loan Balances
            </a>
        </div>
    </div>

    <p class="text-sm text-gray-500 mb-6">
        What employees have applied for themselves. Approving one creates the
        loan and its ledger entry; until then nothing is owed.
    </p>

    @include('loan.partials.flash')

    @if($approverCount === 0)
        <div class="p-3 rounded mb-4" style="background:#fef9c3; color:#a16207;">
            Nobody is set up to be told about loan applications yet.
            <a href="{{ route('admin.loan.approvers.index') }}" class="underline">
                Add the approvers
            </a>
            so they get the WhatsApp message.
        </div>
    @elseif(!$wahaEnabled)
        <div class="p-3 rounded mb-4" style="background:#fef9c3; color:#a16207;">
            WhatsApp (WAHA) is switched off, so applications are recorded but no
            message goes out. Applications still show up on this screen.
        </div>
    @endif

    {{-- Filter --}}
    <div class="mb-4" style="display:flex; gap:8px; font-size:14px;">
        @foreach(['' => 'All', 'pending' => 'Waiting', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
            <a href="{{ route('admin.loan.applications', $value ? ['status' => $value] : []) }}"
               style="padding:6px 14px; border-radius:4px; text-decoration:none;
                      background:{{ (string) $status === (string) $value ? '#111827' : '#e5e7eb' }};
                      color:{{ (string) $status === (string) $value ? '#ffffff' : '#374151' }};">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="bg-white shadow rounded" style="overflow-x:auto;">

        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3 text-left">Reference</th>
                    <th class="p-3 text-left">Employee</th>
                    <th class="p-3 text-left">Applied</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Months</th>
                    <th class="p-3 text-left">Monthly</th>
                    <th class="p-3 text-left">Purpose</th>
                    <th class="p-3 text-left">WhatsApp</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($applications as $loan)
                    <tr class="border-t" style="vertical-align:top;">

                        <td class="p-3 whitespace-nowrap font-semibold">
                            {{ $loan->reference() }}
                        </td>

                        <td class="p-3">
                            {{ optional($loan->user)->name ?? 'Employee Deleted' }}
                            @if(optional($loan->user)->employee_code)
                                <div class="text-xs text-gray-500">
                                    {{ $loan->user->employee_code }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3 whitespace-nowrap">
                            {{ $loan->requested_at?->format('d M Y') }}
                            <div class="text-xs text-gray-500">
                                {{ $loan->requested_at?->format('h:i A') }}
                            </div>
                        </td>

                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($loan->amount, 2) }}
                        </td>

                        <td class="p-3">{{ $loan->installments }}</td>

                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($loan->monthly_deduction, 2) }}
                            @if($loan->deduction_start_month)
                                <div class="text-xs text-gray-500">
                                    from {{ \Carbon\Carbon::parse($loan->deduction_start_month)->format('M Y') }}
                                </div>
                            @endif
                        </td>

                        <td class="p-3 text-gray-600" style="max-width:220px;">
                            {{ $loan->purpose }}
                        </td>

                        <td class="p-3 text-xs">
                            @php
                                $notify = [
                                    'sent' => ['Sent', '#15803d'],
                                    'partial' => ['Partly sent', '#a16207'],
                                    'failed' => ['Failed', '#b91c1c'],
                                    'skipped' => ['Not sent', '#6b7280'],
                                ][$loan->whatsapp_notify_status] ?? ['-', '#6b7280'];
                            @endphp
                            <span style="color:{{ $notify[1] }};">{{ $notify[0] }}</span>

                            @if($loan->whatsapp_notified_at)
                                <div class="text-gray-400">
                                    {{ $loan->whatsapp_notified_at->format('d M h:i A') }}
                                </div>
                            @endif

                            @if($loan->isPending())
                                <form method="POST"
                                      action="{{ route('admin.loan.applications.resend', $loan->id) }}"
                                      class="inline">
                                    @csrf
                                    <button class="underline" style="color:#2563eb;">Resend</button>
                                </form>
                            @endif
                        </td>

                        <td class="p-3">
                            @include('loan.partials.status-badge', ['loan' => $loan])

                            @if($loan->decided_at)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $loan->decided_by }}
                                    @if($loan->decided_via === 'whatsapp')
                                        (WhatsApp)
                                    @endif
                                    <br>{{ $loan->decided_at->format('d M Y h:i A') }}
                                    @if(filled($loan->decision_remarks))
                                        <br>{{ $loan->decision_remarks }}
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="p-3 whitespace-nowrap">

                            <a href="{{ route('loan.application.print', $loan->id) }}"
                               target="_blank"
                               class="hover:underline" style="color:#2563eb;">Print</a>

                            <span class="text-gray-300">|</span>

                            <a href="{{ route('loan.application.print', [$loan->id, 'pdf' => 1]) }}"
                               class="hover:underline" style="color:#4f46e5;">PDF</a>

                            @if($loan->isPending())
                                <div class="mt-2" style="min-width:230px;">

                                    <form method="POST"
                                          action="{{ route('admin.loan.approve', $loan->id) }}"
                                          onsubmit="return confirm('Approve this loan application?')">
                                        @csrf

                                        <div style="display:flex; gap:4px; margin-bottom:4px;">
                                            <input type="number" name="amount" step="0.01" min="1"
                                                   value="{{ $loan->amount }}"
                                                   title="Approve for a different amount if needed"
                                                   class="border rounded px-2 py-1"
                                                   style="width:110px;">
                                            <input type="number" name="installments" min="1" max="120"
                                                   value="{{ $loan->installments }}"
                                                   title="Months"
                                                   class="border rounded px-2 py-1"
                                                   style="width:70px;">
                                        </div>

                                        <input type="text" name="decision_remarks" maxlength="255"
                                               placeholder="Remarks (optional)"
                                               class="border rounded px-2 py-1 w-full"
                                               style="margin-bottom:4px;">

                                        <button class="text-white px-3 py-1 rounded"
                                                style="background:#16a34a;">
                                            Approve
                                        </button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('admin.loan.reject', $loan->id) }}"
                                          class="mt-3"
                                          onsubmit="return confirm('Reject this loan application?')">
                                        @csrf

                                        <input type="text" name="decision_remarks" maxlength="255"
                                               placeholder="Reason for rejecting (optional)"
                                               class="border rounded px-2 py-1 w-full"
                                               style="margin-bottom:4px;">

                                        <button class="text-white px-3 py-1 rounded"
                                                style="background:#dc2626;">
                                            Reject
                                        </button>
                                    </form>

                                </div>
                            @elseif($loan->status === 'approved')
                                <div class="mt-2">
                                    <a href="{{ route('admin.loan.ledger', $loan->id) }}"
                                       class="hover:underline" style="color:#4338ca;">Ledger</a>
                                </div>
                            @endif

                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center p-6 text-gray-500">
                            No loan applications
                            @if($status) with that status @endif
                            yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>

        </table>

    </div>

</div>

</x-app-layout>

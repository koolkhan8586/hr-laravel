<x-app-layout>
<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6">

    <h2 class="text-2xl font-bold mb-2 text-gray-800">Apply for a Loan</h2>

    <p class="text-sm text-gray-500 mb-6">
        Your application goes straight to whoever approves loans here. You can
        print it or save it as a PDF from the list below, and this page shows
        where it has got to.
    </p>

    @include('loan.partials.flash')

    @if($errors->any())
        <div class="p-3 rounded mb-4" style="background:#fee2e2; color:#b91c1c;">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($outstanding > 0)
        <div class="p-3 rounded mb-4" style="background:#f3f4f6; color:#374151;">
            You still owe <strong>Rs {{ number_format($outstanding, 2) }}</strong> on
            loans already running. Whoever decides this application can see that too.
        </div>
    @endif

    @php
        $pending = $applications->firstWhere('status', 'pending');
    @endphp

    @if($pending)

        <div class="bg-white p-6 rounded shadow mb-8" style="border-left:4px solid #eab308;">
            <h3 class="font-semibold text-gray-800 mb-1">
                Application {{ $pending->reference() }} is waiting for a decision
            </h3>
            <p class="text-sm text-gray-600">
                Rs {{ number_format($pending->amount, 2) }} over
                {{ $pending->installments }} month(s), applied
                {{ $pending->requested_at?->format('d M Y h:i A') }}.
                You can apply again once this one has been decided - or cancel
                it now and apply afresh. Once it has been approved it can no
                longer be cancelled here.
            </p>

            <form method="POST"
                  action="{{ route('loan.application.cancel', $pending->id) }}"
                  class="mt-4"
                  onsubmit="return confirm('Cancel application {{ $pending->reference() }}? The approvers will be told you have taken it back.')">
                @csrf
                @method('DELETE')

                <button class="text-white px-4 py-2 rounded" style="background:#dc2626;">
                    Cancel This Application
                </button>
            </form>
        </div>

    @else

        <form method="POST" action="{{ route('loan.store.request') }}"
              class="bg-white p-6 rounded shadow space-y-5 mb-10">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label class="block font-medium mb-1">Loan Amount (Rs)</label>
                    <input type="number" name="amount" min="1000" step="1"
                           value="{{ old('amount') }}"
                           class="w-full border rounded px-3 py-2"
                           required
                           oninput="updateMonthly()" id="loanAmount">
                    <p class="text-xs text-gray-500 mt-1">Minimum Rs 1,000.</p>
                </div>

                <div>
                    <label class="block font-medium mb-1">Repay Over (Months)</label>
                    <input type="number" name="installments" min="1" max="120"
                           value="{{ old('installments') }}"
                           class="w-full border rounded px-3 py-2"
                           required
                           oninput="updateMonthly()" id="loanInstallments">
                    <p class="text-xs text-gray-500 mt-1" id="monthlyHint">
                        The monthly salary deduction works itself out from these two.
                    </p>
                </div>

            </div>

            <div>
                <label class="block font-medium mb-1">
                    Start Deductions From <span class="text-gray-400 text-sm">(optional)</span>
                </label>
                <input type="month" name="deduction_start_month"
                       value="{{ old('deduction_start_month') }}"
                       class="w-full sm:w-64 border rounded px-3 py-2">
            </div>

            <div>
                <label class="block font-medium mb-1">What is the loan for?</label>
                <textarea name="purpose" rows="4" maxlength="1000"
                          class="w-full border rounded px-3 py-2"
                          placeholder="e.g. School fees for my children, medical treatment, house repairs"
                          required>{{ old('purpose') }}</textarea>
            </div>

            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded"
                    style="background:#16a34a;">
                Submit Application
            </button>

        </form>

        <script>
        function updateMonthly() {
            var amount = parseFloat(document.getElementById('loanAmount').value);
            var months = parseInt(document.getElementById('loanInstallments').value, 10);
            var hint = document.getElementById('monthlyHint');

            if (amount > 0 && months > 0) {
                var monthly = (amount / months).toFixed(2);
                hint.textContent = 'About Rs ' + Number(monthly).toLocaleString() +
                    ' would come off your salary each month.';
            } else {
                hint.textContent = 'The monthly salary deduction works itself out from these two.';
            }
        }
        </script>

    @endif

    {{-- ================= MY APPLICATIONS ================= --}}
    <h3 class="text-xl font-bold mb-4 text-gray-800">My Applications</h3>

    <div class="bg-white border rounded shadow-sm" style="overflow-x:auto;">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="p-3 text-left">Reference</th>
                    <th class="p-3 text-left">Applied</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Months</th>
                    <th class="p-3 text-left">Monthly</th>
                    <th class="p-3 text-left">Purpose</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Application</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr class="border-b">
                        <td class="p-3 whitespace-nowrap font-semibold">
                            {{ $application->reference() }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            {{ $application->requested_at?->format('d M Y') }}
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($application->amount, 2) }}
                        </td>
                        <td class="p-3">{{ $application->installments }}</td>
                        <td class="p-3 whitespace-nowrap">
                            Rs {{ number_format($application->monthly_deduction, 2) }}
                        </td>
                        <td class="p-3 text-gray-600">{{ $application->purpose }}</td>
                        <td class="p-3">
                            @include('loan.partials.status-badge', ['loan' => $application])

                            @if($application->decided_at)
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $application->decided_at->format('d M Y') }}
                                    @if(filled($application->decision_remarks))
                                        - {{ $application->decision_remarks }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="p-3 whitespace-nowrap">
                            <a href="{{ route('loan.application.print', $application->id) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline">Print</a>
                            <span class="text-gray-300">|</span>
                            <a href="{{ route('loan.application.print', [$application->id, 'pdf' => 1]) }}"
                               class="text-indigo-600 hover:underline">PDF</a>

                            @if($application->isCancellableBy(auth()->user()))
                                <form method="POST"
                                      action="{{ route('loan.application.cancel', $application->id) }}"
                                      class="mt-2"
                                      onsubmit="return confirm('Cancel application {{ $application->reference() }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="hover:underline" style="color:#dc2626;">
                                        Cancel
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500">
                            You have not applied for a loan yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-app-layout>

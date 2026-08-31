<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Edit Staff</h2>

    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc ml-6">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-6">

            <div>
                <label class="block font-semibold mb-1">Name</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $staff->user->name) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email', $staff->user->email) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Mobile Number</label>
                <input type="text"
                       name="mobile"
                       value="{{ old('mobile', $staff->user->mobile) }}"
                       placeholder="03001234567"
                       class="w-full border p-2 rounded">
                <p class="text-xs text-gray-500 mt-1">
                    Used for WhatsApp attendance reminders (e.g. 03001234567 or 923001234567).
                </p>
            </div>

            <div>
    <label class="block font-semibold mb-1">Employee Code</label>

    <!-- SHOW (readonly) -->
    <input type="text"
       name="employee_code"
       value="{{ old('employee_code', $staff->employee_id) }}"
       class="w-full border p-2 rounded uppercase">
</div>

            <div>
                <label class="block font-semibold mb-1">CNIC Number</label>
                <input type="text"
                       name="cnic"
                       value="{{ old('cnic', $staff->user->cnic) }}"
                       placeholder="35202-1234567-1"
                       class="w-full border p-2 rounded"
                       maxlength="20">
                <p class="text-xs text-gray-500 mt-1">
                    Shown on the tax deduction sheet (e.g. 35202-1234567-1).
                </p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Department</label>
                <input type="text"
                       name="department"
                       value="{{ old('department', $staff->department) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Designation</label>
                <input type="text"
                       name="designation"
                       value="{{ old('designation', $staff->designation) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary</label>
                <input type="number"
                       step="0.01"
                       name="salary"
                       value="{{ old('salary', $staff->salary) }}"
                       class="w-full border p-2 rounded"
                       required>
            </div>

            @php
                // Legacy rows can hold a zero date, which parses to something
                // no date picker will show. Treat anything implausible as blank
                // so a real date has to be chosen.
                $joiningDate = null;

                try {
                    $parsed = $staff->joining_date
                        ? \Carbon\Carbon::parse($staff->joining_date)
                        : null;

                    if ($parsed && $parsed->year > 1900) {
                        $joiningDate = $parsed->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    $joiningDate = null;
                }
            @endphp

            <div>
                <label class="block font-semibold mb-1">Joining Date</label>
                <input type="date"
                       name="joining_date"
                       value="{{ old('joining_date', $joiningDate) }}"
                       class="w-full border p-2 rounded"
                       required>
                @unless($joiningDate)
                <p class="text-xs text-amber-700 mt-1">No valid joining date on record &mdash; please set one.</p>
                @endunless
            </div>

            <div>
                <label class="block font-semibold mb-1">Role</label>
                <select name="role" class="w-full border p-2 rounded" required>
                    <option value="employee" {{ old('role', $staff->user->role) === 'employee' ? 'selected' : '' }}>Employee</option>
                    <option value="manager" {{ old('role', $staff->user->role) === 'manager' ? 'selected' : '' }}>Manager/Accounts</option>
                    <option value="admin" {{ old('role', $staff->user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary Sheet</label>
                <select name="salary_category" class="w-full border p-2 rounded">
                    <option value="staff" {{ old('salary_category', $staff->user->salary_category) === 'staff' ? 'selected' : '' }}>Staff</option>
                    <option value="teacher" {{ old('salary_category', $staff->user->salary_category) === 'teacher' ? 'selected' : '' }}>Teachers</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Which monthly salary sheet this employee appears on.</p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Account No.</label>
                <input type="text"
                       name="bank_account_no"
                       value="{{ old('bank_account_no', $staff->user->bank_account_no) }}"
                       class="w-full border p-2 rounded">
                <p class="text-xs text-gray-500 mt-1">Credit account used on the bank sheet.</p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Pay Into Another Account</label>
                <select name="bank_payee_id" class="w-full border p-2 rounded">
                    <option value="">Own account</option>
                    @foreach($payees as $payee)
                        <option value="{{ $payee->id }}"
                            {{ old('bank_payee_id', $staff->user->bank_payee_id) == $payee->id ? 'selected' : '' }}>
                            {{ $payee->name }}{{ $payee->employee_code ? ' ('.$payee->employee_code.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    For employees with no account of their own. Their pay is merged into
                    the chosen person's line on the bank sheet.
                </p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Employment Status</label>
                <select name="employment_status" class="w-full border p-2 rounded">
                    <option value="active"
                        {{ old('employment_status', $staff->status) === 'active' ? 'selected' : '' }}>
                        Active
                    </option>
                    <option value="inactive"
                        {{ old('employment_status', $staff->status) === 'inactive' ? 'selected' : '' }}>
                        Left / Resigned
                    </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Left or resigned employees are hidden from daily attendance,
                    absence, late and leave reports.
                </p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Attendance Tracking</label>
                <select name="tracks_attendance" class="w-full border p-2 rounded">
                    <option value="1"
                        {{ old('tracks_attendance', $staff->user->tracksAttendance() ? '1' : '0') === '1' ? 'selected' : '' }}>
                        Marks attendance
                    </option>
                    <option value="0"
                        {{ old('tracks_attendance', $staff->user->tracksAttendance() ? '1' : '0') === '0' ? 'selected' : '' }}>
                        Payroll only &mdash; no attendance
                    </option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Payroll only means they are paid each month but never mark
                    attendance, so they are left out of the absence, late and
                    leave reports instead of showing absent every working day.
                </p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Salary Access</label>
                <label class="flex items-center gap-2 mt-2">
                    <input type="checkbox" name="can_manage_salary" value="1"
                           {{ old('can_manage_salary', $staff->user->can_manage_salary) ? 'checked' : '' }}>
                    <span class="text-sm">Can work on Salary Management</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">
                    Grants access to the salary screens without making them an admin.
                </p>
            </div>

            <div>
                <label class="block font-semibold mb-1">Loan Access</label>
                <label class="flex items-center gap-2 mt-2">
                    <input type="checkbox" name="can_manage_loan" value="1"
                           {{ old('can_manage_loan', $staff->user->can_manage_loan) ? 'checked' : '' }}>
                    <span class="text-sm">Can work on Loan Management</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">
                    Grants access to the loan screens without making them an admin.
                </p>
            </div>

            <div class="mt-3">
    <label class="block font-semibold mb-1">Office Location</label>

    <select name="office_location_id" class="w-full border p-2 rounded">
        <option value="">Select Office</option>

        @foreach($locations as $location)
            <option value="{{ $location->id }}"
                {{ $staff->user->office_location_id == $location->id ? 'selected' : '' }}>
                {{ $location->name }}
            </option>
        @endforeach
    </select>
</div>

            <div class="mt-4">

    <!-- 🔓 Allow Anywhere -->
    <label class="flex items-center gap-2">
        <input type="checkbox" name="allow_anywhere_attendance"
        {{ $staff->allow_anywhere_attendance ? 'checked' : '' }}>
        
        <span class="font-semibold text-green-700">
            Allow Attendance Anywhere
        </span>
    </label>

</div>

<div class="mt-3">

    <!-- ⏱ Temporary Override -->
    <label class="block mb-1 font-semibold">
        Allow Until (Optional)
    </label>

    <input type="datetime-local"
           name="attendance_override_until"
           value="{{ $staff->attendance_override_until }}"
           class="border p-2 w-full rounded">

</div>

        </div>

        <div class="mt-8">
            <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded">
                Update Staff
            </button>

            <a href="{{ route('admin.staff.index') }}"
               class="ml-3 bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded">
                Cancel
            </a>
        </div>

    </form>

</div>
</x-app-layout>

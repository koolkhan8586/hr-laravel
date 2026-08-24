<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

<meta charset="utf-8">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'LSAF-HR') }}</title>

<link rel="icon" type="image/png" href="{{ asset('uol-logo.png') }}">
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- Shared print rules: strip the app chrome so only the page content prints --}}
<style>
@media print {

    /* App chrome never belongs on paper */
    aside,
    header,
    .no-print,
    .fixed {
        display: none !important;
    }

    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Let the content use the full sheet instead of the flex column */
    body > div,
    body > div > div {
        display: block !important;
        min-height: 0 !important;
    }

    main {
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        max-width: none !important;
        flex: none !important;
    }

    .print-area,
    .max-w-7xl,
    .max-w-6xl,
    .max-w-5xl,
    .max-w-full {
        max-width: none !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        box-shadow: none !important;
    }

    /* Tables must not be clipped, and headers repeat on every page */
    .overflow-x-auto {
        overflow: visible !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        page-break-inside: auto;
    }

    thead {
        display: table-header-group;
    }

    tfoot {
        display: table-footer-group;
    }

    tr, td, th {
        page-break-inside: avoid;
    }

    th, td {
        border: 1px solid #999 !important;
    }

    /* Form fields print as plain values */
    input, select, textarea {
        border: none !important;
        background: transparent !important;
        -webkit-appearance: none;
        appearance: none;
        padding: 0 !important;
    }

    input[type=number]::-webkit-outer-spin-button,
    input[type=number]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    a[href]:after {
        content: none !important;
    }
}
</style>

</head>

<body class="font-sans antialiased bg-gray-100">

<div x-data="{sidebar:false}" class="flex min-h-screen">

<!-- MOBILE OVERLAY -->

<div
x-show="sidebar"
class="fixed inset-0 bg-black bg-opacity-40 z-30 md:hidden"
@click="sidebar=false">
</div>

<!-- SIDEBAR -->

<aside
:class="sidebar ? 'translate-x-0' : '-translate-x-full'"
class="fixed md:relative z-40 transform transition-transform duration-200 md:translate-x-0 w-64 bg-white shadow-lg min-h-screen">

<div class="p-6 font-bold text-lg border-b">
LSAF HR
</div>

<nav class="p-4 text-sm space-y-1">

<a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-200">
Dashboard
</a>

<a href="{{ route('employees.index') }}" 
   class="block px-4 py-2 hover:bg-gray-200 rounded">
    Employee Directory
</a>

{{-- ================= ADMIN MENUS ================= --}}
@if(Auth::user()->role === 'admin' || Auth::user()->role === 'manager')

@if(Auth::user()->role === 'admin')
<!-- STAFF -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Staff Management
</button>

<div x-show="open" class="pl-4 space-y-1">
<a href="{{ route('admin.staff.index') }}" class="block py-1 hover:text-blue-600">
Staff List
</a>
</div>
</div>

<!-- ATTENDANCE -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Attendance Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.attendance.dashboard') }}" class="block py-1 hover:text-blue-600">
Attendance Dashboard
</a>

<a href="{{ route('admin.attendance.calendar') }}" class="block py-1 hover:text-blue-600">
Attendance Calendar
</a>

<a href="{{ route('admin.attendance.index') }}" class="block py-1 hover:text-blue-600">
Attendance Management
</a>

<a href="{{ route('admin.attendance.summary') }}" class="block py-1 hover:text-blue-600">
Monthly Summary
</a>

<a href="{{ route('admin.reports.late') }}" class="block py-1 hover:text-blue-600">
Staff Late (Month)
</a>

<a href="{{ route('admin.reports.absent') }}" class="block py-1 hover:text-blue-600">
Staff Absence (Month)
</a>

<a href="{{ route('admin.reports.leave') }}" class="block py-1 hover:text-blue-600">
Staff Leave (Month)
</a>

<a href="{{ route('admin.live.map') }}">
        Live Tracking Map
    </a>



    <a href="{{ route('admin.office-locations.index') }}"
       class="block py-2 px-3 hover:bg-gray-100 rounded">
       
       📍 Office Locations
    </a>


</div>
</div>

<!-- SCHEDULE -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Schedule Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('shifts.index') }}" class="block py-1 hover:text-blue-600">
Shifts
</a>

<a href="{{ route('weekly.schedule') }}" class="block py-1 hover:text-blue-600">
Weekly Schedule
</a>

<a href="{{ route('weekly.schedules') }}" class="block py-1 hover:text-blue-600">
View Weekly Schedules
</a>

<a href="{{ route('schedule.calendar') }}" class="block py-1 hover:text-blue-600">
Schedule Calendar
</a>

<a href="{{ route('schedule.editor') }}" class="block py-1 hover:text-blue-600">
Schedule Grid Editor
</a>

</div>
</div>

<!-- LEAVE -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Leave Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.leave.index') }}" class="block py-1 hover:text-blue-600">
Manage Leaves
</a>

<a href="{{ route('admin.leave.calendar') }}" class="block py-1 hover:text-blue-600">
Leave Calendar
</a>

<a href="{{ route('admin.leave.allocation.index') }}" class="block py-1 hover:text-blue-600">
Leave Allocation
</a>

<a href="{{ route('admin.leave.transactions') }}" class="block py-1 hover:text-blue-600">
Leave Transactions
</a>

<a href="{{ route('admin.leave.approval.emails.index') }}" class="block py-1 hover:text-blue-600">
Approval Notification Emails
</a>

<a href="{{ route('admin.leave.approval.whatsapp.index') }}" class="block py-1 hover:text-blue-600">
Approval WhatsApp Numbers
</a>

</div>
</div>

<!-- COMMUNICATIONS -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Communications
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.settings.index') }}" class="block py-1 hover:text-blue-600">
Settings
</a>

<a href="{{ route('admin.announcements.index') }}" class="block py-1 hover:text-blue-600">
Announcements
</a>

</div>
</div>

<!-- WORK FROM HOME -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Work From Home Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.wfh.index') }}" class="block py-1 hover:text-blue-600">
Manage WFH
</a>

</div>
</div>
@endif
@endif
{{-- ================= END ADMIN / MANAGER MENUS ================= --}}

{{-- Salary Management: admins + staff with Salary Access checkbox --}}
@if(Auth::user()->canManageSalary())
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Salary Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.salary.index') }}" class="block py-1 hover:text-blue-600">
Salary Management
</a>

<a href="{{ route('admin.salary.sheet') }}" class="block py-1 hover:text-blue-600">
Salary Sheet
</a>

<a href="{{ route('admin.salary.bank.sheet') }}" class="block py-1 hover:text-blue-600">
Bank Sheet
</a>

<a href="{{ route('admin.salary.bank.letter') }}" class="block py-1 hover:text-blue-600">
Bank Letter
</a>

<a href="{{ route('admin.salary.medical') }}" class="block py-1 hover:text-blue-600">
Medical Insurance
</a>

<a href="{{ route('admin.salary.tax.sheet') }}" class="block py-1 hover:text-blue-600">
Tax Sheet
</a>

<a href="{{ route('admin.salary.tax') }}" class="block py-1 hover:text-blue-600">
Tax Calculate
</a>

<a href="{{ route('admin.salary.columns') }}" class="block py-1 hover:text-blue-600">
Sheet Columns
</a>

</div>
</div>
@endif

{{-- Loan Management: admins, managers, or staff with Loan Access checkbox --}}
@if(Auth::user()->canManageLoans())
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Loan Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.loan.create') }}" class="block py-1 hover:text-blue-600">
Loan Opening Balance
</a>

<a href="{{ route('admin.loan.index') }}" class="block py-1 hover:text-blue-600">
Loan Management
</a>

</div>
</div>
@endif

{{-- ================= EMPLOYEE MENUS ================= --}}

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Employee Panel
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('attendance.index') }}" class="block py-1 hover:text-blue-600">
My Attendance
</a>

<a href="{{ route('leave.index') }}" class="block py-1 hover:text-blue-600">
My Leave
</a>

<a href="{{ route('salary.index') }}" class="block py-1 hover:text-blue-600">
My Salary
</a>

<a href="{{ route('loan.my') }}" class="block py-1 hover:text-blue-600">
My Loans
</a>

<a href="{{ route('cafe.launch') }}" class="block py-1 hover:text-blue-600">
Cafe
</a>

<a href="{{ route('profile.edit') }}" class="block py-1 hover:text-blue-600">
Profile
</a>

<a href="{{ route('holidays.index') }}" class="block py-2 hover:text-blue-600">
Holiday Calendar
</a>

<a href="{{ route('employee.wfh') }}" class="block py-2 hover:text-blue-600">
Work From Home
</a>

</div>
</div>

</nav>

</aside>

<!-- MAIN CONTENT -->

<div class="flex-1 flex flex-col w-full">

<!-- HEADER (WEB LAYOUT HEADER) -->

<header class="bg-white shadow flex items-center justify-between px-6 py-4">

    <!-- LEFT SIDE -->
    <div class="flex items-center gap-3">

        <button @click="sidebar=true" class="text-2xl">
            ☰
        </button>

        <div class="font-semibold text-lg">
            {{ $header ?? 'LSAF HR' }}
        </div>

    </div>

    <!-- RIGHT SIDE -->
    <div class="flex items-center gap-3 ml-auto">

        <!-- REFRESH BUTTON (MAINLY FOR MOBILE APP) -->
        <button onclick="location.reload()"
        class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-2 py-1 rounded text-sm">
            🔄
        </button>

        <span class="text-sm font-medium hidden sm:block">
            {{ Auth::user()->name }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                Logout
            </button>
        </form>

    </div>

</header>

<main class="flex-1 p-6 pb-20">
{{ $slot }}
</main>

</div>

</div>

<!-- MOBILE BOTTOM NAVIGATION -->

<div class="fixed bottom-0 left-0 w-full bg-white border-t shadow md:hidden z-50">

<div class="grid grid-cols-5 text-center text-xs">

<a href="{{ route('dashboard') }}" class="py-2 hover:bg-gray-100">
<div>🏠</div>
<div>Home</div>
</a>

<a href="{{ route('attendance.index') }}" class="py-2 hover:bg-gray-100">
<div>⏰</div>
<div>Attendance</div>
</a>

<a href="{{ route('leave.index') }}" class="py-2 hover:bg-gray-100">
<div>📅</div>
<div>Leave</div>
</a>

<a href="{{ route('salary.index') }}" class="py-2 hover:bg-gray-100">
<div>💰</div>
<div>Salary</div>
</a>

<a href="{{ route('profile.edit') }}" class="py-2 hover:bg-gray-100">
<div>👤</div>
<div>Profile</div>
</a>

</div>

</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>
</html>

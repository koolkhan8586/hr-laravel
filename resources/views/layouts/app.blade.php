<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'LSAF-HR') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

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

{{-- ================= ADMIN MENUS ================= --}}
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

</div>
</div>

<!-- SALARY -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Salary Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<a href="{{ route('admin.salary.index') }}" class="block py-1 hover:text-blue-600">
Salary Management
</a>

</div>
</div>

<!-- LOANS -->

<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200 font-semibold">
Loan Management
</button>

<div x-show="open" class="pl-4 space-y-1">

<li>
    <a href="{{ route('admin.loan.create') }}">
        Loan Opening Balance
    </a>
</li>
<a href="{{ route('admin.loan.index') }}" class="block py-1 hover:text-blue-600">
Loan Management
</a>

</div>
</div>

@endif
{{-- ================= END ADMIN MENUS ================= --}}

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

<a href="{{ route('profile.edit') }}" class="block py-1 hover:text-blue-600">
Profile
</a>

</div>
</div>

</nav>

</aside>

<!-- MAIN CONTENT -->

<div class="flex-1 flex flex-col w-full">

<!-- HEADER -->

<header class="bg-white shadow flex justify-between items-center px-6 py-4">

<div class="flex items-center space-x-3">

<button @click="sidebar=true" class="md:hidden text-xl">
☰ </button>

<div class="font-semibold text-lg">
{{ $header ?? '' }}
</div>

</div>

<div class="flex items-center space-x-4">

<span class="text-sm">
{{ Auth::user()->name }}
</span>

<form method="POST" action="{{ route('logout') }}">
@csrf
<button class="bg-red-500 text-white px-3 py-1 rounded text-sm">
Logout
</button>
</form>

</div>

</header>

<main class="flex-1 p-6">
{{ $slot }}
</main>

</div>

</div>

</body>
</html>

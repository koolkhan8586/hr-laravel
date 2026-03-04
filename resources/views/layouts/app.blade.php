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

<div x-data="{ sidebarOpen:false }" class="flex min-h-screen">




<!-- SIDEBAR -->
<aside
:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
class="fixed md:relative z-40 md:translate-x-0 transform transition-transform duration-200 w-64 bg-white shadow-lg min-h-screen">

<div class="p-6 text-lg font-bold border-b">
LSAF HR
</div>


<nav class="p-4 space-y-2 text-sm">

<a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-200">
Dashboard
</a>



<!-- STAFF MANAGEMENT -->

<div class="font-semibold text-gray-500 mt-4">Staff Management</div>

<a href="{{ route('admin.staff.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Staff Management
</a>




<!-- ATTENDANCE -->

<div class="font-semibold text-gray-500 mt-4">Attendance Management</div>

<a href="{{ route('admin.attendance.dashboard') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Attendance Dashboard
</a>

<a href="{{ route('admin.attendance.calendar') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Attendance Calendar
</a>

<a href="{{ route('admin.attendance.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Attendance Management
</a>




<!-- SCHEDULE -->

<div class="font-semibold text-gray-500 mt-4">Schedule Management</div>

<a href="{{ route('shifts.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Shifts
</a>

<a href="{{ route('weekly.schedule') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Weekly Schedule
</a>

<a href="{{ route('weekly.schedules') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
View Weekly Schedules
</a>

<a href="{{ route('schedule.calendar') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Schedule Calendar
</a>

<a href="{{ route('schedule.editor') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Schedule Grid Editor
</a>




<!-- LEAVE -->

<div class="font-semibold text-gray-500 mt-4">Leave Management</div>

<a href="{{ route('admin.leave.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Manage Leaves
</a>

<a href="{{ route('admin.leave.calendar') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Leave Calendar
</a>

<a href="{{ route('admin.leave.allocation.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Leave Allocation
</a>

<a href="{{ route('admin.leave.transactions') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Leave Transactions
</a>




<!-- SALARY -->

<div class="font-semibold text-gray-500 mt-4">Salary Management</div>

<a href="{{ route('admin.salary.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Salary Management
</a>




<!-- LOAN -->

<div class="font-semibold text-gray-500 mt-4">Loan Management</div>

<a href="{{ route('admin.loan.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Loan Management
</a>




<!-- EMPLOYEE -->

<div class="font-semibold text-gray-500 mt-4">Employee</div>

<a href="{{ route('attendance.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
My Attendance
</a>

<a href="{{ route('leave.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
My Leave
</a>

<a href="{{ route('salary.index') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
My Salary
</a>

<a href="{{ route('loan.my') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
My Loans
</a>

<a href="{{ route('profile.edit') }}" class="block px-3 py-2 hover:bg-gray-200 rounded">
Profile
</a>

</nav>

</aside>




<!-- MAIN CONTENT -->
<div class="flex-1 flex flex-col w-full">



<!-- HEADER -->
<header class="bg-white shadow flex justify-between items-center px-6 py-4">

<div class="flex items-center space-x-4">

<!-- MOBILE MENU BUTTON -->

<button @click="sidebarOpen = !sidebarOpen"
class="md:hidden text-xl">
☰
</button>

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




<!-- PAGE CONTENT -->
<main class="flex-1 p-6">

{{ $slot }}

</main>

</div>


</div>

</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ config('app.name', 'LSAF-HR') }}</title>

<!-- Fonts -->
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

<!-- Vite Assets -->
@vite(['resources/css/app.css', 'resources/js/app.js'])

</head>


<body class="font-sans antialiased bg-gray-100">

<div class="flex min-h-screen">


<!-- Sidebar -->
<aside class="w-64 bg-white shadow-lg">

<div class="p-6 text-lg font-bold border-b">
LSAF HR
</div>

<nav class="p-4 space-y-2">

<a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-200">
Dashboard
</a>


<!-- Attendance -->
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200">
Attendance
</button>

<div x-show="open" class="pl-4">

<a href="/attendance"
class="block px-3 py-1 hover:text-blue-600">
Attendance Dashboard
</a>

<a href="/attendance-calendar"
class="block px-3 py-1 hover:text-blue-600">
Attendance Calendar
</a>

</div>
</div>


<!-- Schedule -->
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200">
Schedules
</button>

<div x-show="open" class="pl-4">

<a href="/shifts" class="block px-3 py-1 hover:text-blue-600">
Shifts
</a>

<a href="/weekly-schedules" class="block px-3 py-1 hover:text-blue-600">
Weekly Schedule
</a>

<a href="/schedule-calendar" class="block px-3 py-1 hover:text-blue-600">
Schedule Calendar
</a>

<a href="/schedule-editor" class="block px-3 py-1 hover:text-blue-600">
Schedule Grid Editor
</a>

</div>
</div>


<!-- Leave -->
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200">
Leave
</button>

<div x-show="open" class="pl-4">

<a href="/leave" class="block px-3 py-1 hover:text-blue-600">
Leave Requests
</a>

<a href="/leave-calendar" class="block px-3 py-1 hover:text-blue-600">
Leave Calendar
</a>

</div>
</div>


<!-- Salary -->
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200">
Salary
</button>

<div x-show="open" class="pl-4">

<a href="/salary" class="block px-3 py-1 hover:text-blue-600">
Salary Slips
</a>

</div>
</div>


<!-- Loans -->
<div x-data="{open:false}">
<button @click="open=!open"
class="w-full text-left px-3 py-2 rounded hover:bg-gray-200">
Loans
</button>

<div x-show="open" class="pl-4">

<a href="/loans" class="block px-3 py-1 hover:text-blue-600">
Loan Requests
</a>

</div>
</div>


</nav>

</aside>
<!-- Main Content -->
<div class="flex-1 flex flex-col">


<!-- Top Header -->
<header class="bg-white shadow flex justify-between items-center px-6 py-4">

<div class="font-semibold text-lg">

{{ $header ?? '' }}

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



<!-- Page Content -->
<main class="flex-1 p-6">

{{ $slot }}

</main>


</div>


</div>


</body>
</html>

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

<a href="{{ route('dashboard') }}"
class="block px-3 py-2 rounded hover:bg-gray-200">
Dashboard
</a>


<a href="/attendance"
class="block px-3 py-2 rounded hover:bg-gray-200">
Attendance
</a>


<a href="/shifts"
class="block px-3 py-2 rounded hover:bg-gray-200">
Shifts
</a>


<a href="/weekly-schedules"
class="block px-3 py-2 rounded hover:bg-gray-200">
Weekly Schedule
</a>


<a href="/schedule-calendar"
class="block px-3 py-2 rounded hover:bg-gray-200">
Schedule Calendar
</a>


<a href="/schedule-editor"
class="block px-3 py-2 rounded hover:bg-gray-200">
Schedule Grid Editor
</a>


<a href="/leave"
class="block px-3 py-2 rounded hover:bg-gray-200">
Leave
</a>


<a href="/salary"
class="block px-3 py-2 rounded hover:bg-gray-200">
Salary
</a>


<a href="/loans"
class="block px-3 py-2 rounded hover:bg-gray-200">
Loans
</a>


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

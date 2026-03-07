<x-app-layout>

<x-slot name="header">

<!-- MOBILE HEADER -->
<div class="mobile-header flex items-center justify-between w-full">

    <!-- LEFT SIDE -->
    <div class="flex items-center gap-3">

        <button @click="sidebar=true" class="text-2xl md:hidden">
            ☰
        </button>

        <span class="font-semibold text-lg">
            LSAF HR
        </span>

    </div>

    <!-- RIGHT SIDE -->
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="bg-red-500 text-white px-3 py-1 rounded text-xs shadow hover:bg-red-600">
            Logout
        </button>
    </form>

</div>

</x-slot>

<div class="space-y-4">

<!-- GREETING -->

<div class="bg-white p-4 rounded-xl shadow">
<h2 class="text-lg font-semibold">
As-salamu alaykum (السلام عليكم) {{ Auth::user()->name }}
</h2>
<p class="text-sm text-gray-500">
Welcome to your HR dashboard
</p>
</div>

<!-- CLOCK IN / CLOCK OUT -->

<div class="bg-white p-5 rounded-xl shadow text-center">

<h3 class="text-gray-600 mb-2">Attendance</h3>

<a href="{{ route('attendance.index') }}"
class="bg-green-500 text-white px-6 py-2 rounded-lg shadow hover:bg-green-600">
Clock In / Clock Out
</a>

</div>

<!-- WORKING TIME -->

<div class="bg-white p-5 rounded-xl shadow text-center">

<h3 class="text-gray-600 mb-2">Working Time Today</h3>

<div class="text-2xl font-bold text-blue-600">
{{ now()->format('H:i') }}
</div>

</div>

<!-- QUICK ACTIONS -->

<div class="grid grid-cols-2 gap-3">

<a href="{{ route('attendance.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">⏰</div>
<div class="text-sm mt-1">My Attendance</div>
</a>

<a href="{{ route('leave.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">📅</div>
<div class="text-sm mt-1">My Leave</div>
</a>

<a href="{{ route('salary.index') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">💰</div>
<div class="text-sm mt-1">Salary Slips</div>
</a>

<a href="{{ route('loan.my') }}"
class="bg-white shadow rounded-xl p-4 text-center hover:bg-gray-50">

<div class="text-2xl">🏦</div>
<div class="text-sm mt-1">My Loans</div>
</a>

</div>

</div>

</x-app-layout>

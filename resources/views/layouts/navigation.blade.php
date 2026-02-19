<nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center h-16">

            <!-- LEFT SIDE -->
            <div class="flex items-center space-x-10">

                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-8 w-auto object-contain"
                         style="max-width:140px;">
                </a>

                <!-- Main Menu -->
                <div class="flex items-center space-x-8 text-sm font-medium text-gray-700">

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="hover:text-green-700 transition">
                        Dashboard
                    </a>

                    <!-- Attendance -->
                    <a href="{{ route('attendance.index') }}"
                       class="hover:text-green-700 transition">
                        Attendance
                    </a>

                    <!-- Leave -->
                    <a href="{{ route('leave.index') }}"
                       class="hover:text-green-700 transition">
                        Leave
                    </a>

                    <!-- Salary (Both Admin & Employee) -->
                    <a href="{{ route('salary.index') }}"
                       class="hover:text-green-700 transition">
                        Salary
                    </a>

                    <!-- Loans Section -->
                    @if(auth()->user()->role === 'admin')

                        <!-- Admin Loan Management -->
                        <a href="{{ route('admin.loan.index') }}"
                           class="hover:text-green-700 transition">
                            Loans
                        </a>

                    @else

                        <!-- Employee Loan Links -->
                        <a href="{{ route('loan.my') }}"
                           class="hover:text-green-700 transition">
                            My Loans
                        </a>

                        <a href="{{ route('loan.apply') }}"
                           class="hover:text-green-700 transition">
                            Apply Loan
                        </a>

                    @endif


                    <!-- Admin Dropdown -->
                    <!-- Admin Dropdown -->
@if(auth()->user()->role === 'admin')
<div x-data="{ open: false }" class="relative">

    <button @click="open = !open"
            class="hover:text-green-700 transition flex items-center space-x-1 focus:outline-none">
        <span>Admin</span>
        <span class="text-xs">▼</span>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition
         class="absolute left-0 mt-2 w-56 bg-white border rounded-md shadow-lg z-50">

        <a href="{{ route('admin.staff.index') }}"
           class="block px-4 py-2 hover:bg-gray-100">
            Staff Management
        </a>

        <a href="{{ route('admin.attendance.index') }}"
   class="block px-4 py-2 hover:bg-gray-100">
   Attendance Management
</a>

        <a href="{{ route('admin.leave.index') }}"
           class="block px-4 py-2 hover:bg-gray-100">
            Manage Leaves
        </a>

        <a href="{{ route('admin.leave.balance.index') }}"
   class="block px-4 py-2 hover:bg-gray-100">
    Leave Allocation
</a>


        <a href="{{ route('admin.leave.transactions') }}"
           class="block px-4 py-2 hover:bg-gray-100">
            Leave Transactions
        </a>

        <a href="{{ route('admin.salary.index') }}"
           class="block px-4 py-2 hover:bg-gray-100">
            Salary Management
        </a>

        <a href="{{ route('admin.loan.index') }}"
           class="block px-4 py-2 hover:bg-gray-100">
            Loan Management
        </a>

    </div>
</div>
@endif




                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="flex items-center space-x-6">

                <span class="text-sm text-gray-700 font-medium">
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white text-sm px-4 py-2 rounded shadow">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </div>
</nav>

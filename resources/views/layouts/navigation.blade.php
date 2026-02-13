<nav x-data="{ open: false }" class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between h-16 items-center">

            {{-- LEFT SIDE --}}
            <div class="flex items-center space-x-10">

                {{-- LOGO --}}
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-10 w-auto">
                </a>

                {{-- MAIN NAVIGATION --}}
                <div class="hidden md:flex items-center space-x-6 text-sm font-medium">

                    <a href="{{ route('dashboard') }}"
                       class="hover:text-green-700 {{ request()->routeIs('dashboard') ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="hover:text-green-700 {{ request()->routeIs('attendance.*') ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="hover:text-green-700 {{ request()->routeIs('leave.index') ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                        Leave
                    </a>

                    {{-- EMPLOYEE LOAN VIEW --}}
                    @if(auth()->user()->role === 'employee')
                        <a href="{{ route('loan.my') }}"
                           class="hover:text-green-700 {{ request()->routeIs('loan.my') ? 'text-green-700 font-semibold' : 'text-gray-700' }}">
                            My Loan
                        </a>
                    @endif

                    {{-- ADMIN DROPDOWN --}}
                    @if(auth()->user()->role === 'admin')
                        <div class="relative" x-data="{ adminOpen: false }">

                            <button @click="adminOpen = !adminOpen"
                                    class="flex items-center space-x-1 text-gray-700 hover:text-green-700">
                                <span>Admin</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="adminOpen"
                                 @click.away="adminOpen = false"
                                 class="absolute mt-2 w-56 bg-white shadow-lg rounded-lg py-2 z-50">

                                <a href="{{ route('staff.index') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Staff Management
                                </a>

                                <a href="{{ route('leave.admin') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Leave Management
                                </a>

                                <a href="{{ route('leave.transactions') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Leave Transactions
                                </a>

                                <a href="{{ route('payroll.summary') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Payroll Summary
                                </a>

                                <a href="{{ route('loan.index') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Loan Management
                                </a>

                            </div>
                        </div>
                    @endif

                </div>
            </div>

            {{-- RIGHT SIDE --}}
            <div class="flex items-center space-x-4">

                <span class="text-gray-600 font-medium">
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-1 rounded shadow">
                        Logout
                    </button>
                </form>

            </div>
        </div>
    </div>
</nav>

<nav x-data="{ open: false, adminOpen: false }"
     class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between h-16 items-center">

            <!-- LEFT -->
            <div class="flex items-center space-x-10">

                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-8 w-auto object-contain">
                </a>

                <!-- Main Menu -->
                <div class="hidden sm:flex items-center space-x-8 text-sm font-medium">

                    <x-nav-link :href="route('dashboard')"
                        :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    <x-nav-link :href="route('attendance.index')"
                        :active="request()->routeIs('attendance.*')">
                        Attendance
                    </x-nav-link>

                    <x-nav-link :href="route('leave.index')"
                        :active="request()->routeIs('leave.index')">
                        Leave
                    </x-nav-link>

                    <!-- ADMIN DROPDOWN -->
                    @if(auth()->user()->role === 'admin')
                        <div class="relative">

                            <button @click="adminOpen = !adminOpen"
                                    class="flex items-center space-x-1 text-gray-700 hover:text-green-600 transition">
                                <span>Admin</span>
                                <svg class="w-4 h-4"
                                     fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="adminOpen"
                                 @click.away="adminOpen = false"
                                 x-transition
                                 class="absolute mt-3 w-56 bg-white border rounded-lg shadow-lg py-2">

                                <a href="{{ route('staff.index') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Staff Management
                                </a>

                                <a href="{{ route('leave.admin') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Manage Leaves
                                </a>

                                <a href="{{ route('leave.transactions') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Transactions
                                </a>

                                <a href="{{ route('payroll.summary') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Payroll Summary
                                </a>

                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <!-- RIGHT -->
            <div class="hidden sm:flex items-center space-x-5">

                <span class="text-gray-600 text-sm">
                    {{ Auth::user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm shadow transition">
                        Logout
                    </button>
                </form>

            </div>

            <!-- Mobile Toggle -->
            <div class="sm:hidden">
                <button @click="open = !open"
                        class="p-2 text-gray-600">
                    ☰
                </button>
            </div>

        </div>
    </div>

</nav>

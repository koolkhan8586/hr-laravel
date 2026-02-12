<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow-sm">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            <!-- LEFT SECTION -->
            <div class="flex items-center">

                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('UOL-Green-V1.png') }}"
                             alt="University of Lahore Logo"
                             class="h-8 w-auto object-contain">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:ml-10 space-x-8 text-sm font-medium text-gray-700">

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

                    @if(auth()->user()->role === 'admin')

                        <x-nav-link :href="route('staff.index')" 
                            :active="request()->routeIs('staff.*')">
                            Staff
                        </x-nav-link>

                        <x-nav-link :href="route('leave.admin')" 
                            :active="request()->routeIs('leave.admin')">
                            Manage Leaves
                        </x-nav-link>

                        <x-nav-link :href="route('leave.transactions')" 
                            :active="request()->routeIs('leave.transactions')">
                            Transactions
                        </x-nav-link>

                        <x-nav-link :href="route('payroll.summary')" 
                            :active="request()->routeIs('payroll.summary')">
                            Payroll Summary
                        </x-nav-link>

                    @endif

                </div>
            </div>

            <!-- RIGHT SECTION -->
            <div class="hidden sm:flex sm:items-center space-x-4">

                <span class="text-gray-600 text-sm">
                    {{ Auth::user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm shadow">
                        Logout
                    </button>
                </form>

            </div>

            <!-- Mobile Hamburger -->
            <div class="sm:hidden">
                <button @click="open = !open"
                        class="p-2 rounded-md text-gray-600 hover:bg-gray-100">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" class="sm:hidden bg-white border-t border-gray-200">

        <div class="px-4 pt-4 pb-3 space-y-2 text-sm">

            <a href="{{ route('dashboard') }}" class="block text-gray-700">Dashboard</a>
            <a href="{{ route('attendance.index') }}" class="block text-gray-700">Attendance</a>
            <a href="{{ route('leave.index') }}" class="block text-gray-700">Leave</a>

            @if(auth()->user()->role === 'admin')
                <a href="{{ route('staff.index') }}" class="block text-gray-700">Staff</a>
                <a href="{{ route('leave.admin') }}" class="block text-gray-700">Manage Leaves</a>
                <a href="{{ route('leave.transactions') }}" class="block text-gray-700">Transactions</a>
                <a href="{{ route('payroll.summary') }}" class="block text-gray-700">Payroll Summary</a>
            @endif

            <hr>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left text-red-600">
                    Logout
                </button>
            </form>

        </div>
    </div>

</nav>

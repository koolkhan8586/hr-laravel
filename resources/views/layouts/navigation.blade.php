<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            <!-- Left Section -->
            <div class="flex">

                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <!-- Desktop Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">

                    <!-- Dashboard -->
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    <!-- Attendance -->
                    <x-nav-link :href="route('attendance.index')" 
                        :active="request()->routeIs('attendance.*')">
                        Attendance
                    </x-nav-link>

                    <!-- Leave -->
                    <x-nav-link :href="route('leave.index')" 
                        :active="request()->routeIs('leave.*')">
                        Leave
                    </x-nav-link>

                    <!-- Admin Only Links -->
                    @auth
    @if(auth()->user()->role === 'admin')

        <x-nav-link :href="route('staff.index')" 
            :active="request()->routeIs('staff.*')">
            Staff
        </x-nav-link>

    @endif
@endauth

                    @if(auth()->user()->role === 'admin')

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

            <!-- Right Section (User Dropdown) -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 bg-white rounded-md hover:text-gray-800 focus:outline-none">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">

                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>

                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }"
                            class="inline-flex" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }"
                            class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Mobile Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">

        <div class="pt-2 pb-3 space-y-1">

            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('attendance.index')" :active="request()->routeIs('attendance.*')">
                Attendance
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('leave.index')" :active="request()->routeIs('leave.*')">
                Leave
            </x-responsive-nav-link>

            @if(auth()->user()->role === 'admin')

            <x-responsive-nav-link :href="route('staff.index')">
        Staff
    </x-responsive-nav-link>    
            <x-responsive-nav-link :href="route('leave.admin')">
                    Manage Leaves
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('leave.transactions')">
                    Transactions
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('payroll.summary')">
                    Payroll Summary
                </x-responsive-nav-link>

            @endif

        </div>

        <!-- Mobile User Info -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">
                    {{ Auth::user()->name }}
                </div>
                <div class="font-medium text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </div>
            </div>

            <div class="mt-3 space-y-1">

                <x-responsive-nav-link :href="route('profile.edit')">
                    Profile
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>

            </div>
        </div>

    </div>
</nav>

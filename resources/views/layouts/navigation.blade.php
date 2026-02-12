<nav x-data="{ open: false }" class="bg-white border-b border-gray-200 shadow">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- LEFT SECTION --}}
            <div class="flex items-center space-x-8">

                {{-- LOGO --}}
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <img src="{{ asset('UOL-Green-V1.png') }}"
                             alt="University of Lahore"
                             class="h-12 w-auto">
                    </a>
                </div>

                {{-- MAIN NAVIGATION --}}
                <div class="hidden sm:flex space-x-6">

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

            {{-- RIGHT SECTION --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-4">

                <div class="text-gray-700 font-medium">
                    {{ Auth::user()->name }}
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">
                        Logout
                    </button>
                </form>

            </div>

            {{-- MOBILE HAMBURGER --}}
            <div class="sm:hidden flex items-center">
                <button @click="open = ! open"
                        class="text-gray-600 focus:outline-none">
                    ☰
                </button>
            </div>

        </div>
    </div>

    {{-- MOBILE MENU --}}
    <div :class="{'block': open, 'hidden': !open}"
         class="hidden sm:hidden bg-white border-t border-gray-200 p-4 space-y-3">

        <a href="{{ route('dashboard') }}" class="block">Dashboard</a>
        <a href="{{ route('attendance.index') }}" class="block">Attendance</a>
        <a href="{{ route('leave.index') }}" class="block">Leave</a>

        @if(auth()->user()->role === 'admin')
            <a href="{{ route('staff.index') }}" class="block">Staff</a>
            <a href="{{ route('leave.admin') }}" class="block">Manage Leaves</a>
            <a href="{{ route('leave.transactions') }}" class="block">Transactions</a>
            <a href="{{ route('payroll.summary') }}" class="block">Payroll Summary</a>
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="text-red-600">Logout</button>
        </form>

    </div>

</nav>

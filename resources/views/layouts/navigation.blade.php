<nav x-data="{ open: false }"
     class="bg-white border-b border-gray-200 shadow-sm h-16 flex items-center">

    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">

            {{-- ================= Logo ================= --}}
            <div class="flex items-center space-x-6">

                <a href="{{ route('dashboard') }}" class="flex items-center">
    <img src="{{ asset('UOL-Green-V1.png') }}"
         alt="University of Lahore"
         class="h-8 w-auto max-h-8 object-contain">
</a>


                {{-- Main Navigation --}}
                <div class="hidden sm:flex space-x-6 text-sm font-medium">

                    <a href="{{ route('dashboard') }}"
                       class="{{ request()->routeIs('dashboard') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="{{ request()->routeIs('attendance.*') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="{{ request()->routeIs('leave.index') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                        Leave
                    </a>

                    {{-- Admin Only --}}
                    @if(auth()->user()->role === 'admin')

                        <a href="{{ route('staff.index') }}"
                           class="{{ request()->routeIs('staff.*') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                            Staff
                        </a>

                        <a href="{{ route('leave.admin') }}"
                           class="{{ request()->routeIs('leave.admin') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                            Manage Leaves
                        </a>

                        <a href="{{ route('leave.transactions') }}"
                           class="{{ request()->routeIs('leave.transactions') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                            Transactions
                        </a>

                        <a href="{{ route('payroll.summary') }}"
                           class="{{ request()->routeIs('payroll.summary') ? 'text-blue-600 border-b-2 border-blue-600 pb-1' : 'text-gray-600 hover:text-blue-600' }}">
                            Payroll Summary
                        </a>

                    @endif

                </div>
            </div>

            {{-- ================= Right Section ================= --}}
            <div class="flex items-center space-x-4">

                <span class="text-gray-700 font-medium">
                    {{ Auth::user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded shadow text-sm">
                        Logout
                    </button>
                </form>

                {{-- Mobile Hamburger --}}
                <button @click="open = !open"
                        class="sm:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }"
                              class="inline-flex"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': !open, 'inline-flex': open }"
                              class="hidden"
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

            </div>
        </div>
    </div>

    {{-- ================= Mobile Menu ================= --}}
    <div x-show="open" class="sm:hidden bg-white border-t border-gray-200 px-4 py-4 space-y-2">

        <a href="{{ route('dashboard') }}" class="block text-gray-700">Dashboard</a>
        <a href="{{ route('attendance.index') }}" class="block text-gray-700">Attendance</a>
        <a href="{{ route('leave.index') }}" class="block text-gray-700">Leave</a>

        @if(auth()->user()->role === 'admin')
            <a href="{{ route('staff.index') }}" class="block text-gray-700">Staff</a>
            <a href="{{ route('leave.admin') }}" class="block text-gray-700">Manage Leaves</a>
            <a href="{{ route('leave.transactions') }}" class="block text-gray-700">Transactions</a>
            <a href="{{ route('payroll.summary') }}" class="block text-gray-700">Payroll Summary</a>
        @endif

    </div>

</nav>

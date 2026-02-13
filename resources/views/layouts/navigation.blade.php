<nav class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center h-14">

            {{-- LEFT SECTION --}}
            <div class="flex items-center space-x-8">

                {{-- LOGO (FIXED SIZE + NO OVERFLOW) --}}
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-8 w-auto object-contain">
                </a>

                {{-- MAIN MENU --}}
                <div class="hidden md:flex items-center space-x-6 text-sm font-medium">

                    <a href="{{ route('dashboard') }}"
                       class="{{ request()->routeIs('dashboard') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="{{ request()->routeIs('attendance.*') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="{{ request()->routeIs('leave.index') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                        Leave
                    </a>

                    {{-- ADMIN MENU --}}
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('staff.index') }}"
                           class="{{ request()->routeIs('staff.*') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            Staff
                        </a>

                        <a href="{{ route('leave.admin') }}"
                           class="{{ request()->routeIs('leave.admin') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            Manage Leaves
                        </a>

                        <a href="{{ route('leave.transactions') }}"
                           class="{{ request()->routeIs('leave.transactions') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            Transactions
                        </a>

                        <a href="{{ route('payroll.summary') }}"
                           class="{{ request()->routeIs('payroll.summary') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            Payroll
                        </a>

                        <a href="{{ route('loan.index') }}"
                           class="{{ request()->routeIs('loan.*') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            Loans
                        </a>
                    @endif

                    {{-- EMPLOYEE LOAN --}}
                    @if(auth()->user()->role === 'employee')
                        <a href="{{ route('loan.my') }}"
                           class="{{ request()->routeIs('loan.my') ? 'text-green-700 font-semibold' : 'text-gray-700 hover:text-green-700' }}">
                            My Loan
                        </a>
                    @endif

                </div>
            </div>

            {{-- RIGHT SIDE --}}
            <div class="flex items-center space-x-4">

                <span class="text-gray-600 text-sm font-medium">
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="bg-red-500 hover:bg-red-600 text-white px-4 py-1 rounded text-sm">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </div>
</nav>

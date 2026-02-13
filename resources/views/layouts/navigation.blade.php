<nav class="bg-white border-b shadow">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between items-center h-16">

            {{-- LEFT SECTION --}}
            <div class="flex items-center space-x-8">

                {{-- LOGO --}}
                <a href="{{ route('dashboard') }}">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-10 w-auto object-contain">
                </a>

                {{-- MENU LINKS --}}
                <div class="flex items-center space-x-6 text-sm font-medium">

                    <a href="{{ route('dashboard') }}"
                       class="hover:text-green-700">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="hover:text-green-700">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="hover:text-green-700">
                        Leave
                    </a>

                    {{-- ADMIN LINKS --}}
                    @if(auth()->user()->role === 'admin')

                        <a href="{{ route('staff.index') }}"
                           class="hover:text-green-700">
                            Staff
                        </a>

                        <a href="{{ route('leave.admin') }}"
                           class="hover:text-green-700">
                            Manage Leaves
                        </a>

                        <a href="{{ route('leave.transactions') }}"
                           class="hover:text-green-700">
                            Transactions
                        </a>

                        <a href="{{ route('payroll.summary') }}"
                           class="hover:text-green-700">
                            Payroll
                        </a>

                        <a href="{{ route('loan.index') }}"
                           class="hover:text-green-700">
                            Loans
                        </a>

                    @endif

                    {{-- EMPLOYEE LOAN --}}
                    @if(auth()->user()->role === 'employee')
                        <a href="{{ route('loan.my') }}"
                           class="hover:text-green-700">
                            My Loan
                        </a>
                    @endif

                </div>

            </div>

            {{-- RIGHT SECTION --}}
            <div class="flex items-center space-x-4">

                <span class="text-sm text-gray-600">
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

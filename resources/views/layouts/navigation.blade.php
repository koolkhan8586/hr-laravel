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
                         style="max-width:120px;">
                </a>

                <!-- Main Menu -->
                <div class="flex items-center space-x-8 text-sm font-medium text-gray-700">

                    <a href="{{ route('dashboard') }}"
                       class="hover:text-green-700 transition">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="hover:text-green-700 transition">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="hover:text-green-700 transition">
                        Leave
                    </a>

                    <a href="{{ route('loan.index') }}"
                       class="hover:text-green-700 transition">
                        Loans
                    </a>

                    @if(auth()->user()->role === 'admin')

                        <a href="{{ route('staff.index') }}"
                           class="hover:text-green-700 transition">
                            Staff
                        </a>

                        <a href="{{ route('leave.admin') }}"
                           class="hover:text-green-700 transition">
                            Manage Leaves
                        </a>

                        <a href="{{ route('leave.transactions') }}"
                           class="hover:text-green-700 transition">
                            Transactions
                        </a>

                        <a href="{{ route('payroll.summary') }}"
                           class="hover:text-green-700 transition">
                            Payroll
                        </a>

                    @endif

                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="flex items-center space-x-6">

                <span class="text-sm text-gray-600">
                    {{ auth()->user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm shadow">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </div>
</nav>

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

                    <a href="{{ route('admin.loan.index') }}"

                       class="hover:text-green-700 transition">
                        Loans
                    </a>

                    @if(auth()->user()->role === 'admin')
                        <div class="relative group">
                            <button class="hover:text-green-700 transition flex items-center space-x-1">
                                <span>Admin</span>
                                <span class="text-xs">▼</span>
                            </button>

                            <div class="absolute left-0 mt-2 w-48 bg-white border rounded-md shadow-lg hidden group-hover:block z-50">

                                <a href="{{ route('staff.index') }}"
                                   class="block px-4 py-2 hover:bg-gray-100">
                                    Staff
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
                                    Payroll
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

<nav class="bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <!-- LEFT SIDE -->
            <div class="flex items-center space-x-8">

                <!-- LOGO -->
                <a href="{{ route('dashboard') }}" class="flex items-center">
                    <img src="{{ asset('UOL-Green-V1.png') }}"
                         alt="UOL Logo"
                         class="h-8 w-auto object-contain"
                         style="max-width:150px;">
                </a>

                <!-- MAIN MENU -->
                <div class="flex items-center space-x-6 text-sm font-medium">


                    <a href="{{ route('dashboard') }}"
                       class="text-gray-700 hover:text-green-600 transition">
                        Dashboard
                    </a>

                    <a href="{{ route('attendance.index') }}"
                       class="text-gray-700 hover:text-green-600 transition">
                        Attendance
                    </a>

                    <a href="{{ route('leave.index') }}"
                       class="text-gray-700 hover:text-green-600 transition">
                        Leave
                    </a>

                    <a href="{{ route('loan.index') }}"
                       class="text-gray-700 hover:text-green-600 transition">
                        Loans
                    </a>

                    @if(auth()->user()->role === 'admin')
                        <!-- Admin Dropdown -->
                        <div class="relative group">
                            <button class="text-gray-700 hover:text-green-600 focus:outline-none">
                                Admin ▾
                            </button>

                            <div class="absolute hidden group-hover:block bg-white shadow-lg rounded-md mt-2 w-48 py-2 z-50">
                                <a href="{{ route('staff.index') }}"
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    Staff Management
                                </a>

                                <a href="{{ route('leave.admin') }}"
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    Manage Leaves
                                </a>

                                <a href="{{ route('leave.transactions') }}"
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    Transactions
                                </a>

                                <a href="{{ route('payroll.summary') }}"
                                   class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    Payroll Summary
                                </a>
                            </div>
                        </div>
                    @endif

                </div>
            </div>

            <!-- RIGHT SIDE -->
            <div class="flex items-center space-x-4">

                <span class="text-gray-700 text-sm font-medium">
                    {{ Auth::user()->name }}
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-1.5 rounded-md text-sm transition shadow">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </div>
</nav>

<x-app-layout>

<div class="max-w-7xl mx-auto py-8">

    <h2 class="text-xl font-bold mb-6">Salary Import Preview</h2>

    <form action="{{ route('admin.salary.import.confirm') }}" method="POST">
        @csrf

        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th>User ID</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Basic Salary</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['user_id'] }}</td>
                    <td>{{ $row['month'] }}</td>
                    <td>{{ $row['year'] }}</td>
                    <td>{{ $row['basic_salary'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <button type="submit"
                class="bg-green-600 text-white px-4 py-2 rounded mt-4">
            Confirm Import
        </button>
    </form>

</div>

</x-app-layout>

<x-app-layout>

<div class="max-w-7xl mx-auto py-6 px-4">

<h2 class="text-2xl font-bold mb-6">Holiday Management</h2>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-3 rounded mb-4">
{{ session('success') }}
</div>
@endif

<!-- ADD HOLIDAY -->

<div class="bg-white p-4 shadow rounded mb-6">

<form method="POST" action="{{ route('admin.holidays.store') }}">
@csrf

<div class="grid grid-cols-4 gap-4">

<input type="text"
name="title"
placeholder="Holiday Title"
class="border rounded px-3 py-2"
required>

<input type="date"
name="start_date"
class="border rounded px-3 py-2"
required>

<input type="date"
name="end_date"
class="border rounded px-3 py-2"
required>

<select name="for_all"
class="border rounded px-3 py-2"
id="holidayType">

<option value="1">All Employees</option>
<option value="0">Specific Employees</option>

</select>

</div>

<div class="mt-3" id="employeeSelect" style="display:none">

<select name="user_id[]"
multiple
id="employeeSelectBox"
class="w-full border rounded px-3 py-2">

@foreach($employees as $employee)

<option value="{{ $employee->id }}">
{{ $employee->name }}
</option>

@endforeach

</select>

</div>

<button class="mt-4 bg-green-600 text-white px-4 py-2 rounded">
Add Holiday
</button>

</form>

</div>

<!-- HOLIDAY LIST -->

<div class="bg-white shadow rounded overflow-x-auto">

<table class="min-w-full text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-3 text-left">Title</th>
<th class="p-3 text-left">Date</th>
<th class="p-3 text-left">Type</th>
<th class="p-3 text-left">Action</th>
</tr>
</thead>

<tbody>

@foreach($holidays as $holiday)

<tr class="border-t">

<td class="p-3">
{{ $holiday->title }}
</td>

<td class="p-3">
{{ $holiday->start_date }} - {{ $holiday->end_date }}
</td>

<td class="p-3">

@if($holiday->for_all)
All Employees
@else
Specific Employee
@endif

</td>

<td class="p-3">

<form method="POST"
action="{{ route('admin.holidays.delete',$holiday->id) }}">
@csrf
@method('DELETE')

<button class="bg-red-500 text-white px-3 py-1 rounded">
Delete
</button>

</form>

</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<script>

document.addEventListener("DOMContentLoaded", function () {

    // Holiday type change
    document.getElementById('holidayType')
    .addEventListener('change',function(){

        if(this.value == "0"){
            document.getElementById('employeeSelect').style.display="block";
        }else{
            document.getElementById('employeeSelect').style.display="none";
        }

    });


    // Activate Select2
    $('#employeeSelectBox').select2({
        placeholder: "Search and select employees",
        width: '100%'
    });

});

</script>

</x-app-layout>

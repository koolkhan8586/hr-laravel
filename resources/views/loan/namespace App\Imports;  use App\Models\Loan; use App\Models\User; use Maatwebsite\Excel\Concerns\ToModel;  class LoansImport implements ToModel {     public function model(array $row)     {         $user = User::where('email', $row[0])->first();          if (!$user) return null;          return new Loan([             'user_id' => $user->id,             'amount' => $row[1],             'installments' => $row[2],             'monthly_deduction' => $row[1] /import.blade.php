<x-app-layout>
<div class="max-w-4xl mx-auto py-8 px-6">

    <h2 class="text-2xl font-bold mb-6">Import Loans</h2>

    <form method="POST"
          action="{{ route('admin.loan.import') }}"
          enctype="multipart/form-data">
        @csrf

        <input type="file" name="file"
               class="border p-2 rounded"
               required>

        <button class="bg-green-600 text-white px-4 py-2 rounded">
            Upload
        </button>
    </form>

</div>
</x-app-layout>

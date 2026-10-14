{{--
    The flash messages the loan screens set: success, then whatever the
    WhatsApp notification had to say about itself, then errors.

    Inline colours, because this project's Tailwind build does not always
    include classes that only appear in newer views.
--}}

@if(session('success'))
<div class="p-3 rounded mb-4" style="background:#dcfce7; color:#15803d;">
    {{ session('success') }}
</div>
@endif

@if(session('info'))
<div class="p-3 rounded mb-4" style="background:#dbeafe; color:#1d4ed8;">
    {{ session('info') }}
</div>
@endif

@if(session('warning'))
<div class="p-3 rounded mb-4" style="background:#fef9c3; color:#a16207;">
    {{ session('warning') }}
</div>
@endif

@if(session('error'))
<div class="p-3 rounded mb-4" style="background:#fee2e2; color:#b91c1c;">
    {{ session('error') }}
</div>
@endif

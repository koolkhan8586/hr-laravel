@php
    $palette = [
        'pending'   => ['Waiting',  '#fef9c3', '#a16207'],
        'approved'  => ['Approved', '#dcfce7', '#15803d'],
        'rejected'  => ['Rejected', '#fee2e2', '#b91c1c'],
        'closed'    => ['Paid Off', '#e5e7eb', '#374151'],
        'completed' => ['Paid Off', '#e5e7eb', '#374151'],
    ];

    [$label, $background, $colour] = $palette[$loan->status]
        ?? [ucfirst((string) $loan->status), '#e5e7eb', '#374151'];
@endphp

<span style="display:inline-block; padding:2px 8px; border-radius:9999px; font-size:12px;
             background:{{ $background }}; color:{{ $colour }};">
    {{ $label }}
</span>

{{--
    The popup both the dashboard and the attendance page use when clocking in
    or out. Include it once per page; everything is driven by attendanceAction().

    It replaces a browser alert(), which on a phone is a cramped grey box, and
    replaces the plain form submit the dashboard used to do - that navigated
    away and left the employee staring at raw JSON.
--}}

<div id="attendanceModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 px-4">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md text-center p-8">

        <div id="attendanceModalIcon" class="text-6xl mb-4">📍</div>

        <h3 id="attendanceModalTitle" class="text-2xl font-bold mb-3 text-gray-800"></h3>

        <p id="attendanceModalMessage"
           class="text-lg leading-relaxed text-gray-700 mb-6"></p>

        <a id="attendanceModalLink" href="{{ route('location-requests.mine') }}"
           class="hidden text-blue-600 hover:underline text-sm block mb-5">
            See where I can clock in, or ask for a change
        </a>

        <button type="button" onclick="closeAttendanceModal()"
                class="bg-gray-800 hover:bg-gray-900 text-white px-8 py-3 rounded-lg text-lg w-full">
            OK
        </button>

    </div>
</div>

<script>
function showAttendanceModal(ok, message, showLocationLink) {

    const modal = document.getElementById('attendanceModal');

    document.getElementById('attendanceModalIcon').textContent = ok ? '✅' : '🚫';
    document.getElementById('attendanceModalTitle').textContent = ok ? 'Done' : 'Cannot mark attendance';
    document.getElementById('attendanceModalMessage').textContent = message;

    document.getElementById('attendanceModalLink')
        .classList.toggle('hidden', !showLocationLink);

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Remember whether to refresh once it is dismissed.
    modal.dataset.reload = ok ? '1' : '';
}

function closeAttendanceModal() {

    const modal = document.getElementById('attendanceModal');

    modal.classList.add('hidden');
    modal.classList.remove('flex');

    if (modal.dataset.reload === '1') {
        location.reload();
    }
}

/**
 * Clock in or out without leaving the page.
 *
 * The button is disabled while it is in flight, because tapping twice on a
 * slow connection used to send two clock-ins.
 */
function attendanceAction(url, button) {

    if (!navigator.geolocation) {
        showAttendanceModal(false, 'This device cannot provide a location, which is needed to mark attendance.', false);
        return;
    }

    const original = button ? button.textContent : null;

    if (button) {
        button.disabled = true;
        button.textContent = 'Checking location...';
    }

    const restore = () => {
        if (button) {
            button.disabled = false;
            button.textContent = original;
        }
    };

    navigator.geolocation.getCurrentPosition(

        function (position) {

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                        || '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                }),
            })
            .then(res => res.json().catch(() => ({
                success: false,
                message: 'Something went wrong. Please try again, or tell HR if it keeps happening.',
            })))
            .then(data => {
                restore();
                // Only a location refusal is worth pointing at that page.
                const aboutLocation = !data.success
                    && /location|within|away|from/i.test(data.message || '');
                showAttendanceModal(!!data.success, data.message || 'Done.', aboutLocation);
            })
            .catch(() => {
                restore();
                showAttendanceModal(false, 'Could not reach the server. Please check your connection and try again.', false);
            });
        },

        function () {
            restore();
            showAttendanceModal(false,
                'Location is switched off. Please allow location access for this site and try again.', false);
        },

        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}
</script>

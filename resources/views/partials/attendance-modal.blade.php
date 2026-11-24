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

{{-- The live photo, for the employees it is switched on for. Camera only:
     there is deliberately no way to choose a picture already on the phone. --}}
<div id="selfieModal"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md text-center p-6">

        <h3 class="text-xl font-bold mb-1 text-gray-800">Take your photo</h3>
        <p class="text-sm text-gray-600 mb-4">
            A photo is taken when you clock in. Look at the camera.
        </p>

        <video id="selfieVideo" autoplay playsinline muted
               class="w-full rounded-lg bg-black mb-4"
               style="transform: scaleX(-1); max-height: 55vh; object-fit: cover;"></video>

        <canvas id="selfieCanvas" class="hidden"></canvas>

        <div class="flex gap-3">
            <button type="button" id="selfieCancel"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-3 rounded-lg">
                Cancel
            </button>
            <button type="button" id="selfieTake"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-semibold">
                Take photo and clock in
            </button>
        </div>
    </div>
</div>

<script>
// Whether this employee has to give a photo to clock in. The server decides
// again, so this only decides whether to ask for the camera.
const SELFIE_REQUIRED = @json((bool) auth()->user()?->selfie_required);

/**
 * Open the front camera and resolve with a small JPEG, or null if the
 * employee cancelled. Rejects with a readable message if the camera cannot
 * be used.
 */
function captureSelfie() {

    return new Promise((resolve, reject) => {

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            reject('This browser cannot open the camera, which is needed to clock in.');
            return;
        }

        navigator.mediaDevices
            .getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(stream => {

                const modal  = document.getElementById('selfieModal');
                const video  = document.getElementById('selfieVideo');
                const canvas = document.getElementById('selfieCanvas');

                video.srcObject = stream;
                modal.classList.remove('hidden');
                modal.classList.add('flex');

                const finish = (result) => {
                    stream.getTracks().forEach(t => t.stop());
                    video.srcObject = null;
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    resolve(result);
                };

                document.getElementById('selfieCancel').onclick = () => finish(null);

                document.getElementById('selfieTake').onclick = () => {

                    if (!video.videoWidth) {
                        return; // the camera has not produced a picture yet
                    }

                    // Shrunk before it is sent: a phone photo is several
                    // megabytes, and a face needs far fewer pixels than that.
                    const width  = Math.min(480, video.videoWidth);
                    const height = Math.round(video.videoHeight * (width / video.videoWidth));

                    canvas.width  = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(video, 0, 0, width, height);

                    finish(canvas.toDataURL('image/jpeg', 0.7));
                };
            })
            .catch(() => reject('The camera is switched off. Please allow camera access for this site and try again.'));
    });
}

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
 * This browser's own token.
 *
 * Kept in the browser's storage so the same browser is recognisable from
 * one day to the next. It says nothing about who is using it - it is there
 * so that one office computer marking attendance for five different people
 * can be seen for what it is.
 */
function attendanceDeviceId() {

    const key = 'lsaf_attendance_device';

    try {
        let id = window.localStorage.getItem(key);

        if (!id) {
            id = (window.crypto && window.crypto.randomUUID)
                ? window.crypto.randomUUID()
                : 'd-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 12);

            window.localStorage.setItem(key, id);
        }

        return id;
    } catch (e) {
        // Private windows and blocked storage: no token rather than no
        // attendance. The server still sees the browser and the address.
        return null;
    }
}

/**
 * Clock in or out without leaving the page.
 *
 * The button is disabled while it is in flight, because tapping twice on a
 * slow connection used to send two clock-ins.
 */
function attendanceAction(url, button) {

    // Clocking out never needs a photo; clocking in does for some employees.
    if (SELFIE_REQUIRED && /clock-in/.test(url)) {

        captureSelfie()
            .then(photo => {
                if (photo) {
                    sendAttendance(url, button, photo);
                }
            })
            .catch(message => showAttendanceModal(false, message, false));

        return;
    }

    sendAttendance(url, button, null);
}

function sendAttendance(url, button, selfie) {

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
                    device_id: attendanceDeviceId(),
                    // A phone has a touch screen; a desktop browser
                    // dressed as one usually forgets to say so.
                    touch_points: navigator.maxTouchPoints ?? 0,
                    selfie: selfie,
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

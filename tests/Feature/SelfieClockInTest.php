<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use App\Models\WeeklySchedule;
use App\Support\AttendanceSelfie;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A live photo at clock-in, for the one employee it is switched on for.
 */
class SelfieClockInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['mail.default' => 'array']);
        Storage::fake(AttendanceSelfie::DISK);

        Carbon::setTestNow(Carbon::parse('2026-11-24 09:00:00', 'Asia/Karachi'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** A real JPEG, as a data URL the way the browser sends it. */
    private function photo(int $side = 200): string
    {
        // A plain colour is exactly what the quality check refuses, so the
        // test photo has a background and a lighter face-shaped patch in it.
        $img = imagecreatetruecolor($side, $side);
        imagefill($img, 0, 0, imagecolorallocate($img, 60, 70, 90));
        imagefilledellipse($img, intdiv($side, 2), intdiv($side, 2), intdiv($side, 2), intdiv($side * 3, 4), imagecolorallocate($img, 190, 150, 130));

        ob_start();
        imagejpeg($img, null, 70);
        $bytes = ob_get_clean();

        return 'data:image/jpeg;base64,'.base64_encode($bytes);
    }

    private function employee(bool $selfie, string $name = 'Employee'): User
    {
        $user = User::factory()->create([
            'name' => $name, 'role' => 'employee', 'tracks_attendance' => true,
            'selfie_required' => $selfie,
        ]);

        $shift = Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        return $user;
    }

    private function clockIn(User $user, array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile/15E148'])
            ->postJson(route('attendance.clockin'), $extra + [
                'latitude' => 31.5, 'longitude' => 74.3,
                'device_id' => 'device-aaaaaaaa', 'touch_points' => 5,
            ]);
    }

    public function test_an_employee_who_must_give_a_photo_is_refused_without_one(): void
    {
        $ali = $this->employee(true);

        $this->clockIn($ali)
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        // Refused means nothing was written at all.
        $this->assertDatabaseMissing('attendances', ['user_id' => $ali->id]);
    }

    public function test_with_a_photo_the_clock_in_goes_through_and_the_photo_is_kept(): void
    {
        $ali = $this->employee(true);

        $this->clockIn($ali, ['selfie' => $this->photo()])
            ->assertOk()
            ->assertJson(['success' => true]);

        $mark = Attendance::where('user_id', $ali->id)->firstOrFail();

        $this->assertNotNull($mark->selfie_path);
        Storage::disk(AttendanceSelfie::DISK)->assertExists($mark->selfie_path);

        // Kept on the private disk, beside the device and the position.
        $this->assertStringStartsWith('selfies/2026-11/', $mark->selfie_path);
        $this->assertSame('phone', $mark->device_kind);
        $this->assertEquals(31.5, $mark->clock_in_latitude);
    }

    public function test_everybody_else_clocks_in_exactly_as_before(): void
    {
        $other = $this->employee(false, 'Somebody Else');

        $this->clockIn($other)->assertOk();

        $this->assertNull(Attendance::where('user_id', $other->id)->value('selfie_path'));

        // And a photo sent anyway by somebody it is not switched on for is
        // not kept: it is a picture of a person, so nothing is stored that
        // nobody asked for.
        $again = $this->employee(false, 'Another');

        $this->clockIn($again, ['selfie' => $this->photo()])->assertOk();

        $this->assertNull(Attendance::where('user_id', $again->id)->value('selfie_path'));
        $this->assertSame([], Storage::disk(AttendanceSelfie::DISK)->allFiles());
    }

    public function test_something_that_is_not_a_photo_is_refused(): void
    {
        $ali = $this->employee(true);

        $bad = [
            'plain text'          => 'hello',
            'a png header'        => 'data:image/png;base64,'.base64_encode('x'),
            'not really a jpeg'   => 'data:image/jpeg;base64,'.base64_encode('<?php echo 1; ?>'),
            'a jpeg far too small' => $this->photo(20),
            'not base64 at all'   => 'data:image/jpeg;base64,@@@@',
        ];

        foreach ($bad as $why => $data) {
            $this->clockIn($ali, ['selfie' => $data])->assertStatus(422);
        }

        $this->assertDatabaseMissing('attendances', ['user_id' => $ali->id]);
        $this->assertSame([], Storage::disk(AttendanceSelfie::DISK)->allFiles());
    }

    public function test_an_oversized_photo_is_refused(): void
    {
        $ali = $this->employee(true);

        $huge = 'data:image/jpeg;base64,'.base64_encode(str_repeat('a', AttendanceSelfie::MAX_BYTES + 10));

        $this->clockIn($ali, ['selfie' => $huge])->assertStatus(422);
    }

    /*
    |--------------------------------------------------------------------------
    | A covered camera is not a photo
    |--------------------------------------------------------------------------
    */

    private function dataUrl($img): string
    {
        ob_start();
        imagejpeg($img, null, 70);

        return 'data:image/jpeg;base64,'.base64_encode(ob_get_clean());
    }

    /** What a lens with a finger over it looks like: dark red, black at the edges. */
    private function coveredLens()
    {
        $img = imagecreatetruecolor(480, 640);

        for ($y = 0; $y < 640; $y++) {
            for ($x = 0; $x < 480; $x++) {
                $d = sqrt((($x - 240) / 240) ** 2 + (($y - 320) / 320) ** 2);
                $r = max(0, (int) (70 * (1 - $d)));
                imagesetpixel($img, $x, $y, imagecolorallocate($img, $r, 0, intdiv($r, 6)));
            }
        }

        return $img;
    }

    public function test_a_covered_camera_is_refused(): void
    {
        $ali = $this->employee(true);

        $response = $this->clockIn($ali, ['selfie' => $this->dataUrl($this->coveredLens())])
            ->assertStatus(422);

        $this->assertStringContainsString('too dark or the camera is covered', $response->json('message'));

        // Refused means nothing was written, and nothing was kept.
        $this->assertDatabaseMissing('attendances', ['user_id' => $ali->id]);
        $this->assertSame([], Storage::disk(AttendanceSelfie::DISK)->allFiles());
    }

    public function test_a_black_frame_and_a_blank_white_frame_are_refused(): void
    {
        $ali = $this->employee(true);

        $black = imagecreatetruecolor(480, 640);

        $white = imagecreatetruecolor(480, 640);
        imagefill($white, 0, 0, imagecolorallocate($white, 250, 250, 250));

        $this->clockIn($ali, ['selfie' => $this->dataUrl($black)])->assertStatus(422);
        $this->clockIn($ali, ['selfie' => $this->dataUrl($white)])->assertStatus(422);

        $this->assertDatabaseMissing('attendances', ['user_id' => $ali->id]);
    }

    public function test_a_dim_but_real_looking_photo_is_still_accepted(): void
    {
        $ali = $this->employee(true);

        // A dark room: dark background, a lighter face, dark eyes.
        $img = imagecreatetruecolor(480, 640);
        imagefill($img, 0, 0, imagecolorallocate($img, 40, 45, 55));
        imagefilledellipse($img, 240, 300, 260, 340, imagecolorallocate($img, 150, 110, 95));
        imagefilledellipse($img, 190, 260, 30, 18, imagecolorallocate($img, 30, 25, 25));
        imagefilledellipse($img, 290, 260, 30, 18, imagecolorallocate($img, 30, 25, 25));

        $this->clockIn($ali, ['selfie' => $this->dataUrl($img)])->assertOk();

        $this->assertNotNull(Attendance::where('user_id', $ali->id)->value('selfie_path'));
    }

    public function test_the_measures_tell_a_covered_lens_from_a_photo(): void
    {
        $covered = AttendanceSelfie::quality(base64_decode(substr($this->dataUrl($this->coveredLens()), 23)));
        $real    = AttendanceSelfie::quality(base64_decode(substr($this->photo(), 23)));

        $this->assertFalse($covered['ok']);
        $this->assertLessThan(AttendanceSelfie::MIN_BRIGHTNESS, $covered['brightness']);

        $this->assertTrue($real['ok']);
        $this->assertGreaterThanOrEqual(AttendanceSelfie::MIN_BRIGHTNESS, $real['brightness']);
        $this->assertGreaterThanOrEqual(AttendanceSelfie::MIN_DETAIL, $real['detail']);
    }

    public function test_the_page_checks_the_photo_before_sending_it(): void
    {
        $this->actingAs($this->employee(true))
            ->get(route('attendance.index'))
            ->assertOk()
            ->assertSee('selfieLooksUsable', false)
            ->assertSee('id="selfieError"', false)
            ->assertSee('mean >= '.AttendanceSelfie::MIN_BRIGHTNESS, false);
    }

    public function test_clocking_out_never_needs_a_photo(): void
    {
        $ali = $this->employee(true);

        $this->clockIn($ali, ['selfie' => $this->photo()])->assertOk();

        Carbon::setTestNow(Carbon::parse('2026-11-24 17:00:00', 'Asia/Karachi'));

        $this->actingAs($ali)->postJson(route('attendance.clockout'), [
            'latitude' => 31.5, 'longitude' => 74.3,
        ])->assertOk();
    }

    public function test_an_admin_can_see_the_photo_and_it_is_not_cached(): void
    {
        $ali = $this->employee(true);
        $this->clockIn($ali, ['selfie' => $this->photo()])->assertOk();

        $mark = Attendance::where('user_id', $ali->id)->firstOrFail();

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.selfie', $mark->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_an_employee_cannot_see_anybodys_photo(): void
    {
        $ali = $this->employee(true);
        $this->clockIn($ali, ['selfie' => $this->photo()])->assertOk();

        $mark = Attendance::where('user_id', $ali->id)->firstOrFail();

        // Not somebody else, and not even their own.
        $this->actingAs($this->employee(false, 'Nosy'))
            ->get(route('admin.attendance.selfie', $mark->id))
            ->assertForbidden();

        $this->actingAs($ali)
            ->get(route('admin.attendance.selfie', $mark->id))
            ->assertForbidden();
    }

    public function test_a_day_with_no_photo_is_a_404_not_an_error(): void
    {
        $other = $this->employee(false);
        $this->clockIn($other)->assertOk();

        $mark = Attendance::where('user_id', $other->id)->firstOrFail();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.selfie', $mark->id))
            ->assertNotFound();
    }

    public function test_the_lookup_shows_the_photo_and_a_missing_one(): void
    {
        $ali = $this->employee(true, 'Ali Khawaja');
        $this->clockIn($ali, ['selfie' => $this->photo()])->assertOk();

        $mark = Attendance::where('user_id', $ali->id)->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.attendance.devices', ['month' => '2026-11', 'user_id' => $ali->id]))
            ->assertOk()
            ->assertSee(route('admin.attendance.selfie', $mark->id), false);

        // A day that should have had one and does not is said plainly.
        Attendance::create([
            'user_id' => $ali->id, 'date' => '2026-11-20',
            'clock_in' => '2026-11-20 09:00:00', 'status' => 'present',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendance.devices', ['month' => '2026-11', 'user_id' => $ali->id]))
            ->assertSee('None taken');
    }

    public function test_it_is_switched_on_one_person_at_a_time_from_the_staff_record(): void
    {
        $ali = $this->employee(false, 'Ali Khawaja');

        $staff = Staff::create([
            'user_id' => $ali->id, 'employee_id' => 'E'.$ali->id, 'department' => 'IT',
            'designation' => 'Officer', 'salary' => 60000, 'joining_date' => '2024-01-01',
            'status' => 'active',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $staff->id))
            ->assertOk()
            ->assertSee('Must take a photo to clock in');

        $fields = [
            'name' => $ali->name, 'email' => $ali->email, 'employee_code' => 'E'.$ali->id,
            'department' => 'IT', 'designation' => 'Officer', 'salary' => 60000,
            'role' => 'employee', 'joining_date' => '2024-01-01',
        ];

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $staff->id), $fields + ['selfie_required' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $ali->fresh()->selfie_required);

        // Off for everybody else, and off again when unticked.
        $this->assertFalse((bool) $this->employee(false, 'Somebody')->fresh()->selfie_required);

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $staff->id), $fields)
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $ali->fresh()->selfie_required);
    }

    /*
    |--------------------------------------------------------------------------
    | Deleting old photos
    |--------------------------------------------------------------------------
    */

    private function kept(User $user, string $date): Attendance
    {
        $path = 'selfies/'.substr($date, 0, 7).'/'.$user->id.'-'.str_replace('-', '', $date).'-090000-abcd1234.jpg';

        Storage::disk(AttendanceSelfie::DISK)->put($path, 'jpegbytes');

        return Attendance::create([
            'user_id' => $user->id, 'date' => $date,
            'clock_in' => $date.' 09:00:00', 'status' => 'present',
            'selfie_path' => $path,
        ]);
    }

    public function test_photos_older_than_ninety_days_are_deleted_and_newer_ones_kept(): void
    {
        $ali = $this->employee(true);

        // Today is 24 Nov 2026; ninety days back is 26 Aug.
        $old   = $this->kept($ali, '2026-08-25');
        $edge  = $this->kept($ali, '2026-08-26');
        $fresh = $this->kept($ali, '2026-11-20');

        $this->artisan('attendance:prune-selfies')->assertSuccessful();

        Storage::disk(AttendanceSelfie::DISK)->assertMissing($old->selfie_path ?? 'gone');
        $this->assertNull($old->fresh()->selfie_path);

        // The day itself stays; it says the photo was deleted.
        $this->assertNotNull($old->fresh()->selfie_deleted_at);
        $this->assertNotNull($old->fresh()->clock_in);

        // The cutoff day and anything newer are kept.
        $this->assertNotNull($edge->fresh()->selfie_path);
        $this->assertNotNull($fresh->fresh()->selfie_path);
        Storage::disk(AttendanceSelfie::DISK)->assertExists($fresh->fresh()->selfie_path);
    }

    public function test_the_file_really_goes_from_the_disk(): void
    {
        $ali = $this->employee(true);

        $old  = $this->kept($ali, '2026-06-01');
        $path = $old->selfie_path;

        Storage::disk(AttendanceSelfie::DISK)->assertExists($path);

        $this->artisan('attendance:prune-selfies')->assertSuccessful();

        Storage::disk(AttendanceSelfie::DISK)->assertMissing($path);
    }

    public function test_a_dry_run_deletes_nothing(): void
    {
        $ali = $this->employee(true);

        $old  = $this->kept($ali, '2026-06-01');
        $path = $old->selfie_path;

        $this->artisan('attendance:prune-selfies --dry-run')
            ->expectsOutputToContain('1 photo(s) would be deleted')
            ->assertSuccessful();

        Storage::disk(AttendanceSelfie::DISK)->assertExists($path);
        $this->assertSame($path, $old->fresh()->selfie_path);
        $this->assertNull($old->fresh()->selfie_deleted_at);
    }

    public function test_the_period_is_a_setting(): void
    {
        $ali = $this->employee(true);

        $mid = $this->kept($ali, '2026-10-01'); // 54 days ago

        $this->artisan('attendance:prune-selfies')->assertSuccessful();
        $this->assertNotNull($mid->fresh()->selfie_path);

        \App\Models\AppSetting::put(AttendanceSelfie::RETENTION_DAYS, 30);

        $this->artisan('attendance:prune-selfies')->assertSuccessful();
        $this->assertNull($mid->fresh()->selfie_path);
    }

    public function test_a_silly_setting_cannot_wipe_the_photos(): void
    {
        foreach ([0, 1, -5, 'abc'] as $value) {
            \App\Models\AppSetting::put(AttendanceSelfie::RETENTION_DAYS, $value);

            $this->assertGreaterThanOrEqual(7, AttendanceSelfie::retentionDays());
        }

        \App\Models\AppSetting::put(AttendanceSelfie::RETENTION_DAYS, 999999);
        $this->assertSame(3650, AttendanceSelfie::retentionDays());
    }

    public function test_a_stray_file_nothing_points_at_is_removed_but_a_recent_one_is_not(): void
    {
        $disk = Storage::disk(AttendanceSelfie::DISK);

        $disk->put('selfies/2026-05/stray.jpg', 'x');
        touch($disk->path('selfies/2026-05/stray.jpg'), Carbon::parse('2026-05-01')->timestamp);

        // Just written: could be a clock-in that is saving right now.
        $disk->put('selfies/2026-11/new.jpg', 'x');

        $this->artisan('attendance:prune-selfies')->assertSuccessful();

        $disk->assertMissing('selfies/2026-05/stray.jpg');
        $disk->assertExists('selfies/2026-11/new.jpg');
    }

    public function test_days_without_a_photo_are_left_alone(): void
    {
        $ali = $this->employee(true);

        $plain = Attendance::create([
            'user_id' => $ali->id, 'date' => '2026-06-01',
            'clock_in' => '2026-06-01 09:00:00', 'status' => 'present',
        ]);

        $this->artisan('attendance:prune-selfies')->assertSuccessful();

        $this->assertNull($plain->fresh()->selfie_deleted_at);
    }

    public function test_the_lookup_says_an_old_photo_was_deleted_not_that_none_was_taken(): void
    {
        $ali = $this->employee(true, 'Ali Khawaja');
        $this->kept($ali, '2026-08-01');

        $this->artisan('attendance:prune-selfies')->assertSuccessful();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.devices', ['month' => '2026-08', 'user_id' => $ali->id]))
            ->assertOk()
            ->assertSee('Deleted 24 Nov 2026')
            ->assertSee('90-day rule')
            ->assertDontSee('None taken');
    }

    public function test_the_period_is_set_from_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Delete clock-in photos after this many days');

        $this->actingAs($admin)
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 25, 'missing_minutes' => 30, 'selfie_days' => 45,
            ])
            ->assertSessionHas('success');

        $this->assertSame(45, AttendanceSelfie::retentionDays());

        // Below a week is refused rather than quietly accepted.
        $this->actingAs($admin)
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes' => 25, 'missing_minutes' => 30, 'selfie_days' => 2,
            ])
            ->assertSessionHasErrors('selfie_days');

        $this->assertSame(45, AttendanceSelfie::retentionDays());
    }

    public function test_the_page_only_asks_for_the_camera_when_it_is_switched_on(): void
    {
        $with    = $this->employee(true);
        $without = $this->employee(false, 'Somebody Else');

        $this->actingAs($with)->get(route('attendance.index'))
            ->assertOk()->assertSee('SELFIE_REQUIRED = true', false);

        $this->actingAs($without)->get(route('attendance.index'))
            ->assertOk()->assertSee('SELFIE_REQUIRED = false', false);
    }
}

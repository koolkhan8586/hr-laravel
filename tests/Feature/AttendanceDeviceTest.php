<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\AttendanceDevice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Attendance marked through a remote desktop session on an office computer
 * passes every location rule: the machine is standing in the office, so its
 * position and its address are both perfect.
 *
 * So the question becomes what marked it, not where it was marked from.
 */
class AttendanceDeviceTest extends TestCase
{
    use RefreshDatabase;

    /** A phone's user agent, and a desktop's. */
    private const PHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 '
        .'(KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
        .'(KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Carbon::setTestNow(Carbon::parse('2026-11-23 09:00:00', 'Asia/Karachi'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function device(string $kind = 'phone', ?string $id = 'device-aaaaaaaa'): array
    {
        return ['id' => $id, 'kind' => $kind, 'ip' => '203.0.113.7', 'agent' => self::PHONE];
    }

    /*
    |--------------------------------------------------------------------------
    | Telling a phone from a computer
    |--------------------------------------------------------------------------
    */

    public function test_a_desktop_browser_is_a_computer(): void
    {
        $this->assertSame('computer', AttendanceDevice::kind(self::DESKTOP));
    }

    public function test_a_phone_is_a_phone(): void
    {
        $this->assertSame('phone', AttendanceDevice::kind(self::PHONE));
    }

    public function test_nothing_said_is_nothing_claimed(): void
    {
        $this->assertSame('unknown', AttendanceDevice::kind(''));
    }

    public function test_a_desktop_dressed_as_a_phone_without_a_touch_screen_is_a_computer(): void
    {
        // A desktop browser's device emulation sends a phone's user agent.
        // A real phone has a touch screen; the emulation usually forgets.
        $request = \Illuminate\Http\Request::create('/', 'POST', ['touch_points' => 0]);

        $this->assertSame('computer', AttendanceDevice::kind(self::PHONE, $request));
    }

    public function test_a_claim_that_loosens_the_rule_is_not_believed(): void
    {
        // Saying "I have a touch screen" cannot turn a desktop into a phone:
        // only the user agent can say phone, and touch can only argue it down.
        $request = \Illuminate\Http\Request::create('/', 'POST', ['touch_points' => 10]);

        $this->assertSame('computer', AttendanceDevice::kind(self::DESKTOP, $request));
    }

    /*
    |--------------------------------------------------------------------------
    | The phone-only rule
    |--------------------------------------------------------------------------
    */

    public function test_with_both_rules_off_nothing_is_refused(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(AttendanceDevice::check($user, $this->device('computer'))['allowed']);
    }

    public function test_the_phone_rule_refuses_a_computer(): void
    {
        AppSetting::put(AttendanceDevice::REQUIRE_MOBILE, 1);

        $result = AttendanceDevice::check(User::factory()->create(), $this->device('computer'));

        $this->assertFalse($result['allowed']);
        $this->assertSame('not_a_phone', $result['reason']);
        $this->assertStringContainsString('from your own phone', $result['message']);
    }

    public function test_the_phone_rule_lets_a_phone_through(): void
    {
        AppSetting::put(AttendanceDevice::REQUIRE_MOBILE, 1);

        $this->assertTrue(
            AttendanceDevice::check(User::factory()->create(), $this->device('phone'))['allowed']
        );
    }

    public function test_an_exempt_employee_may_still_use_a_computer(): void
    {
        AppSetting::put(AttendanceDevice::REQUIRE_MOBILE, 1);

        $user = User::factory()->create(['attendance_device_exempt' => true]);

        $this->assertSame('exempt', AttendanceDevice::check($user, $this->device('computer'))['reason']);
    }

    /*
    |--------------------------------------------------------------------------
    | One device, one employee a day
    |--------------------------------------------------------------------------
    */

    public function test_a_device_that_already_marked_somebody_else_today_is_refused(): void
    {
        AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        $first  = User::factory()->create(['name' => 'Abdul Ahid']);
        $second = User::factory()->create(['name' => 'Kashif Vicky']);

        $this->markFor($first, 'shared-device-1');

        $result = AttendanceDevice::check($second, $this->device('phone', 'shared-device-1'));

        $this->assertFalse($result['allowed']);
        $this->assertSame('shared_device', $result['reason']);

        // Said plainly: the employee is told whose attendance it already marked.
        $this->assertStringContainsString('Abdul Ahid', $result['message']);
    }

    public function test_the_same_employee_may_clock_out_from_their_own_device(): void
    {
        AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        $user = User::factory()->create();
        $this->markFor($user, 'own-device-1');

        $this->assertTrue(
            AttendanceDevice::check($user, $this->device('phone', 'own-device-1'))['allowed']
        );
    }

    public function test_yesterdays_use_of_a_device_does_not_block_today(): void
    {
        AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        $first  = User::factory()->create();
        $second = User::factory()->create();

        $this->markFor($first, 'shared-device-1', Carbon::now('Asia/Karachi')->subDay());

        // A family phone one person borrowed yesterday is not evidence today.
        $this->assertTrue(
            AttendanceDevice::check($second, $this->device('phone', 'shared-device-1'))['allowed']
        );
    }

    public function test_with_no_device_token_the_shared_rule_has_nothing_to_go_on(): void
    {
        AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        // Blocked browser storage must not lock somebody out of their own day.
        $this->assertTrue(
            AttendanceDevice::check(User::factory()->create(), $this->device('phone', null))['allowed']
        );
    }


    public function test_checking_the_device_does_not_move_the_callers_clock(): void
    {
        $now = Carbon::parse('2026-11-23 17:05:00', 'Asia/Karachi');

        AttendanceDevice::check(User::factory()->create(), $this->device(), $now);

        // Carbon is mutable and the caller is holding this instance as "now".
        // Winding it to midnight inside the check stamped every clock-out at
        // 00:00 and then charged half a day for leaving seventeen hours early.
        $this->assertSame('2026-11-23 17:05:00', $now->format('Y-m-d H:i:s'));
    }

    /*
    |--------------------------------------------------------------------------
    | What the browser sends
    |--------------------------------------------------------------------------
    */

    public function test_a_made_up_device_token_is_thrown_away(): void
    {
        $request = \Illuminate\Http\Request::create('/', 'POST', ['device_id' => "short"]);
        $this->assertNull(AttendanceDevice::describe($request)['id']);

        $request = \Illuminate\Http\Request::create('/', 'POST', ['device_id' => '<script>alert(1)</script>']);
        $this->assertNull(AttendanceDevice::describe($request)['id']);

        $request = \Illuminate\Http\Request::create('/', 'POST', ['device_id' => str_repeat('a', 200)]);
        $this->assertNull(AttendanceDevice::describe($request)['id']);
    }

    public function test_a_real_device_token_is_kept(): void
    {
        $request = \Illuminate\Http\Request::create('/', 'POST', [
            'device_id' => '018fa2c1-3b4d-7c8e-9f01-a2b3c4d5e6f7',
        ]);

        $this->assertSame('018fa2c1-3b4d-7c8e-9f01-a2b3c4d5e6f7', AttendanceDevice::describe($request)['id']);
    }

    /*
    |--------------------------------------------------------------------------
    | The labels the screens read
    |--------------------------------------------------------------------------
    */

    public function test_the_browser_is_named_in_a_few_words(): void
    {
        $this->assertSame('Chrome on Windows', AttendanceDevice::agentLabel(self::DESKTOP));
        $this->assertSame('Safari on iPhone', AttendanceDevice::agentLabel(self::PHONE));
        $this->assertSame('Not recorded', AttendanceDevice::agentLabel(null));
    }

    public function test_an_unrecorded_kind_says_so_rather_than_reading_as_a_phone(): void
    {
        $this->assertSame('Not recorded', AttendanceDevice::kindLabel(null));
        $this->assertSame('Computer', AttendanceDevice::kindLabel('computer'));
    }

    /*
    |--------------------------------------------------------------------------
    | The audit screen
    |--------------------------------------------------------------------------
    */

    public function test_the_devices_screen_flags_one_device_used_by_two_employees(): void
    {
        $first  = User::factory()->create(['name' => 'Abdul Ahid']);
        $second = User::factory()->create(['name' => 'Kashif Vicky']);

        $this->markFor($first, 'shared-device-1', null, 'computer');
        $this->markFor($second, 'shared-device-1', null, 'computer');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.devices', ['month' => '2026-11']))
            ->assertOk();

        $this->assertSame(1, $response->viewData('sharedCount'));

        $response->assertSee('2 different employees have');
        $response->assertSee('Abdul Ahid');
        $response->assertSee('Kashif Vicky');

        // And the same day, which is the part that cannot be innocent.
        $response->assertSee('the same day.');
    }

    public function test_the_devices_screen_lists_who_marked_from_a_computer(): void
    {
        $user = User::factory()->create(['name' => 'Abdul Ahid']);

        $this->markFor($user, 'device-aaaaaaaa', null, 'computer');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.devices', ['month' => '2026-11']))
            ->assertOk();

        $this->assertCount(1, $response->viewData('fromComputer'));
        $response->assertSee('Marked from a computer');
        $response->assertSee('Chrome on Windows');
    }

    public function test_one_phone_with_one_name_is_not_flagged(): void
    {
        $this->markFor(User::factory()->create(), 'device-aaaaaaaa');

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.devices', ['month' => '2026-11']))
            ->assertOk();

        $this->assertSame(0, $response->viewData('sharedCount'));
        $response->assertSee('came from a phone');
    }

    public function test_marks_from_before_this_was_recorded_are_counted_separately(): void
    {
        $user = User::factory()->create();

        // An older row, with no device on it at all.
        Attendance::create([
            'user_id' => $user->id,
            'date'    => '2026-11-10',
            'status'  => 'present',
        ]);

        $response = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.attendance.devices', ['month' => '2026-11']))
            ->assertOk();

        $this->assertSame(1, $response->viewData('unrecorded'));
        $this->assertSame(0, $response->viewData('recorded'));

        // Said plainly, rather than an empty table reading as "clean".
        $response->assertSee('before devices were recorded');
    }

    /*
    |--------------------------------------------------------------------------
    | The settings and the exemption
    |--------------------------------------------------------------------------
    */

    public function test_an_admin_can_switch_both_rules_on(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes'   => 25,
                'missing_minutes' => 30,
                'require_mobile'  => 1,
                'one_per_device'  => 1,
            ])
            ->assertSessionHas('success');

        $this->assertTrue(AttendanceDevice::requiresMobile());
        $this->assertTrue(AttendanceDevice::onePerDevice());

        $this->assertStringContainsString('only be marked from a phone', session('success'));
    }

    public function test_leaving_the_boxes_unticked_switches_the_rules_off(): void
    {
        AppSetting::put(AttendanceDevice::REQUIRE_MOBILE, 1);
        AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.settings.attendance-rules.save'), [
                'early_minutes'   => 25,
                'missing_minutes' => 30,
            ]);

        $this->assertFalse(AttendanceDevice::requiresMobile());
        $this->assertFalse(AttendanceDevice::onePerDevice());
    }

    public function test_the_settings_screen_shows_the_two_switches(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Only allow attendance from a phone')
            ->assertSee('One device, one employee a day');
    }


    /*
    |--------------------------------------------------------------------------
    | Through the real clock-in, which is where it has to work
    |--------------------------------------------------------------------------
    */

    public function test_clocking_in_records_the_device_it_came_from(): void
    {
        $user = $this->rostered();

        $this->clockIn($user, self::PHONE, 'device-aaaaaaaa')
            ->assertOk()
            ->assertJson(['success' => true]);

        $attendance = Attendance::where('user_id', $user->id)->firstOrFail();

        // Recording is always on, whether the rules are or not - otherwise
        // there is nothing to look at when somebody is suspected later.
        $this->assertSame('device-aaaaaaaa', $attendance->device_id);
        $this->assertSame('phone', $attendance->device_kind);
        $this->assertNotNull($attendance->device_agent);
    }

    public function test_the_phone_rule_refuses_the_real_clock_in_from_a_desktop(): void
    {
        \App\Models\AppSetting::put(AttendanceDevice::REQUIRE_MOBILE, 1);

        $user = $this->rostered();

        $this->clockIn($user, self::DESKTOP, 'office-pc-00001')
            ->assertStatus(403)
            ->assertJson(['success' => false]);

        // Refused means refused: nothing on the record at all.
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_the_office_computer_cannot_mark_a_second_employee(): void
    {
        \App\Models\AppSetting::put(AttendanceDevice::ONE_PER_DEVICE, 1);

        $first  = $this->rostered('Abdul Ahid');
        $second = $this->rostered('Kashif Vicky');

        $this->clockIn($first, self::DESKTOP, 'office-pc-00001')->assertOk();

        $response = $this->clockIn($second, self::DESKTOP, 'office-pc-00001')
            ->assertStatus(403);

        $this->assertStringContainsString('Abdul Ahid', $response->json('message'));
        $this->assertDatabaseMissing('attendances', ['user_id' => $second->id]);
    }

    public function test_clocking_out_records_the_device_too(): void
    {
        $user = $this->rostered();

        $this->clockIn($user, self::PHONE, 'device-aaaaaaaa')->assertOk();

        $this->actingAs($user)
            ->withHeaders(['User-Agent' => self::PHONE])
            ->postJson(route('attendance.clockout'), [
                'latitude'  => 31.5,
                'longitude' => 74.3,
                'device_id' => 'device-bbbbbbbb',
            ])
            ->assertOk();

        // The clock-in's device is kept - it is the one that says where the
        // day started. The clock-out only fills what was still blank.
        $this->assertSame(
            'device-aaaaaaaa',
            Attendance::where('user_id', $user->id)->value('device_id')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | The exemption, on the screen the office actually uses
    |--------------------------------------------------------------------------
    */

    public function test_the_exemption_is_set_on_the_staff_record(): void
    {
        $user = User::factory()->create(['role' => 'employee']);

        $staff = \App\Models\Staff::create([
            'user_id'      => $user->id,
            'employee_id'  => 'E'.$user->id,
            'department'   => 'IT',
            'designation'  => 'Officer',
            'salary'       => 60000,
            'joining_date' => '2024-01-01',
            'status'       => 'active',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.staff.edit', $staff->id))
            ->assertOk()
            ->assertSee('May mark attendance from a shared computer');

        $fields = [
            'name'          => $user->name,
            'email'         => $user->email,
            'employee_code' => 'E'.$user->id,
            'department'    => 'IT',
            'designation'   => 'Officer',
            'salary'        => 60000,
            'role'          => 'employee',
            'joining_date'  => '2024-01-01',
        ];

        $this->actingAs($admin)
            ->put(route('admin.staff.update', $staff->id), $fields + ['attendance_device_exempt' => 1])
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) $user->fresh()->attendance_device_exempt);

        // Unticked, they are back under both rules.
        $this->actingAs($admin)
            ->put(route('admin.staff.update', $staff->id), $fields)
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $user->fresh()->attendance_device_exempt);
    }

    /** An employee on a shift today, so clocking in is actually possible. */
    private function rostered(string $name = 'Employee'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
            'tracks_attendance' => true,
        ]);

        $shift = \App\Models\Shift::firstOrCreate(
            ['name' => '9 to 5'],
            ['start_time' => '09:00:00', 'end_time' => '17:00:00', 'grace_minutes' => 15]
        );

        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            \App\Models\WeeklySchedule::setFor($user->id, $day, $shift->id);
        }

        return $user;
    }

    private function clockIn(User $user, string $agent, string $deviceId): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)
            ->withHeaders(['User-Agent' => $agent])
            ->postJson(route('attendance.clockin'), [
                'latitude'     => 31.5,
                'longitude'    => 74.3,
                'device_id'    => $deviceId,
                'touch_points' => str_contains($agent, 'iPhone') ? 5 : 0,
            ]);
    }

    /** Mark attendance for somebody from a given device. */
    private function markFor(
        User $user,
        string $deviceId,
        ?Carbon $date = null,
        string $kind = 'phone'
    ): Attendance {
        $date = $date ?? Carbon::now('Asia/Karachi');

        return Attendance::create([
            'user_id'      => $user->id,
            'date'         => $date->toDateString(),
            'clock_in'     => $date->copy()->setTime(9, 0),
            'status'       => 'present',
            'device_id'    => $deviceId,
            'device_kind'  => $kind,
            'device_ip'    => '203.0.113.7',
            'device_agent' => $kind === 'phone' ? self::PHONE : self::DESKTOP,
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which offices an employee may mark attendance at.
 *
 * users.office_location_id could only hold one, so somebody who works across
 * two campuses had to be left unrestricted. This allows several, and the
 * existing single assignment is carried across so nobody's setup changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('office_location_user')) {
            Schema::create('office_location_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('office_location_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'office_location_id'], 'office_location_user_unique');
            });
        }

        $this->carryOverExistingAssignments();
    }

    public function down(): void
    {
        Schema::dropIfExists('office_location_user');
    }

    /** Everyone already tied to one office keeps it. */
    protected function carryOverExistingAssignments(): void
    {
        $offices = DB::table('office_locations')->pluck('id')->all();

        if (empty($offices)) {
            return;
        }

        DB::table('users')
            ->whereNotNull('office_location_id')
            ->select('id', 'office_location_id')
            ->orderBy('id')
            ->chunk(500, function ($users) use ($offices) {

                $rows = [];

                foreach ($users as $user) {

                    // Skip a pointer to an office that no longer exists.
                    if (!in_array($user->office_location_id, $offices)) {
                        continue;
                    }

                    $exists = DB::table('office_location_user')
                        ->where('user_id', $user->id)
                        ->where('office_location_id', $user->office_location_id)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $rows[] = [
                        'user_id'            => $user->id,
                        'office_location_id' => $user->office_location_id,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];
                }

                if ($rows) {
                    DB::table('office_location_user')->insert($rows);
                }
            });
    }
};

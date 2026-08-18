<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'cnic')) {
                $table->string('cnic', 20)->nullable()->after('employee_code');
                $table->index('cnic', 'users_cnic_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'cnic')) {
                $table->dropIndex('users_cnic_index');
                $table->dropColumn('cnic');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The bank sheet now shows a single "Account No." column. Anything that
     * was only ever entered as the new account number is carried over so it
     * is not silently dropped from the sheet.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'new_account_no')) {
            return;
        }

        DB::table('users')
            ->whereNotNull('new_account_no')
            ->where('new_account_no', '!=', '')
            ->where(function ($q) {
                $q->whereNull('bank_account_no')
                  ->orWhere('bank_account_no', '');
            })
            ->update([
                'bank_account_no' => DB::raw('new_account_no'),
            ]);
    }

    public function down(): void
    {
        // Nothing to undo: the source column is left untouched above.
    }
};

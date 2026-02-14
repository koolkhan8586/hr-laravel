public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('leave_balance');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->decimal('leave_balance',5,2)->default(0);
    });
}

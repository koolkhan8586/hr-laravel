public function up(): void
{
    Schema::create('salaries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        $table->integer('month');
        $table->integer('year');

        // Earnings
        $table->decimal('basic_salary', 12, 2)->default(0);
        $table->decimal('invigilation', 12, 2)->default(0);
        $table->decimal('t_payment', 12, 2)->default(0);
        $table->decimal('Others', 12, 2)->default(0);
        $table->decimal('Eidi', 12, 2)->default(0);
        $table->decimal('increment', 12, 2)->default(0);

        // Deductions
        $table->decimal('extra_leaves', 12, 2)->default(0);
        $table->decimal('income_tax', 12, 2)->default(0);
        $table->decimal('loan_deduction', 12, 2)->default(0);
        $table->decimal('insurance', 12, 2)->default(0);
        $table->decimal('others', 12, 2)->default(0);

        $table->decimal('gross_total', 12, 2)->default(0);
        $table->decimal('total_deductions', 12, 2)->default(0);
        $table->decimal('net_salary', 12, 2)->default(0);

        $table->boolean('is_posted')->default(false);

        $table->timestamps();
    });
}

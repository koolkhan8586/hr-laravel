public function up()
{
    Schema::create('loans', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->decimal('amount', 12, 2);
        $table->integer('installments');
        $table->decimal('monthly_deduction', 12, 2);
        $table->decimal('remaining_amount', 12, 2);
        $table->date('start_date');
        $table->enum('status', ['active', 'completed'])->default('active');
        $table->timestamps();
    });
}

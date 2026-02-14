use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {

            $table->foreignId('loan_id')
                  ->after('id')
                  ->constrained('loans')
                  ->cascadeOnDelete();

            $table->decimal('amount_paid', 12, 2)
                  ->after('loan_id');

            $table->decimal('remaining_balance', 12, 2)
                  ->after('amount_paid');

            $table->integer('month')
                  ->after('remaining_balance');

            $table->integer('year')
                  ->after('month');
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropColumn([
                'loan_id',
                'amount_paid',
                'remaining_balance',
                'month',
                'year'
            ]);
        });
    }
};

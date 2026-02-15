namespace App\Imports;

use App\Models\Loan;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;

class LoansImport implements ToModel
{
    public function model(array $row)
    {
        $user = User::where('email', $row[0])->first();

        if (!$user) return null;

        return new Loan([
            'user_id' => $user->id,
            'amount' => $row[1],
            'installments' => $row[2],
            'monthly_deduction' => $row[1] / $row[2],
            'remaining_balance' => $row[1],
            'status' => 'approved'
        ]);
    }
}

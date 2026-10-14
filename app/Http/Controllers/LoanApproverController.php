<?php

namespace App\Http\Controllers;

use App\Models\LoanApprover;
use App\Services\WahaService;
use Illuminate\Http\Request;

/**
 * Who gets told about a loan application, and may decide it from WhatsApp.
 */
class LoanApproverController extends Controller
{
    public function index()
    {
        return view('admin.loan_approvers.index', [
            'approvers' => LoanApprover::orderBy('id')->get(),
            'waha' => app(WahaService::class)->connectionStatus(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:30|unique:loan_approvers,mobile',
        ]);

        $mobile = trim($data['mobile']);

        // Reject a number WAHA could never send to, rather than storing it and
        // failing silently every time somebody applies.
        if (!app(WahaService::class)->toChatId($mobile)) {
            return back()
                ->withInput()
                ->withErrors(['mobile' => 'That does not look like a WhatsApp number. Use a form like 03001234567.']);
        }

        LoanApprover::create([
            'name' => $data['name'] ?? null,
            'mobile' => $mobile,
            'is_active' => true,
        ]);

        return back()->with('success', 'Loan approver added.');
    }

    public function toggle($id)
    {
        $approver = LoanApprover::findOrFail($id);

        $approver->update(['is_active' => ! $approver->is_active]);

        return back()->with('success', $approver->is_active
            ? 'Loan approver switched on.'
            : 'Loan approver switched off - they will no longer be messaged.');
    }

    public function destroy($id)
    {
        LoanApprover::findOrFail($id)->delete();

        return back()->with('success', 'Loan approver removed.');
    }

    /** Send a test message, so the number can be checked before it matters. */
    public function test($id)
    {
        $approver = LoanApprover::findOrFail($id);

        $ok = app(WahaService::class)->sendToMobile(
            $approver->mobile,
            "Test message from LSAF HR.\n\nYou are set up to receive loan applications for approval on this number."
        );

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Test message sent to '.$approver->mobile.'.'
            : 'Could not send to '.$approver->mobile.'. Check the number and the WAHA session.');
    }
}

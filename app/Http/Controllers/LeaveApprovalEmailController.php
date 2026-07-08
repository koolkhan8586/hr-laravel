<?php

namespace App\Http\Controllers;

use App\Models\LeaveApprovalEmail;
use Illuminate\Http\Request;

class LeaveApprovalEmailController extends Controller
{
    public function index()
    {
        $emails = LeaveApprovalEmail::latest()->get();

        return view('admin.leave_approval_emails.index', compact('emails'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'required|email|unique:leave_approval_emails,email',
        ]);

        LeaveApprovalEmail::create($request->only('name', 'email'));

        return back()->with('success', 'Approval email added');
    }

    public function destroy($id)
    {
        LeaveApprovalEmail::findOrFail($id)->delete();

        return back()->with('success', 'Approval email removed');
    }
}

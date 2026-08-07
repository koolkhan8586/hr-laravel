<?php

namespace App\Http\Controllers;

use App\Models\LeaveApprovalWhatsappNumber;
use Illuminate\Http\Request;

class LeaveApprovalWhatsappController extends Controller
{
    public function index()
    {
        $numbers = LeaveApprovalWhatsappNumber::latest()->get();

        return view('admin.leave_approval_whatsapp.index', compact('numbers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'mobile' => 'required|string|max:30|unique:leave_approval_whatsapp_numbers,mobile',
        ]);

        LeaveApprovalWhatsappNumber::create([
            'name' => $request->name,
            'mobile' => trim($request->mobile),
        ]);

        return back()->with('success', 'Approval WhatsApp number added');
    }

    public function destroy($id)
    {
        LeaveApprovalWhatsappNumber::findOrFail($id)->delete();

        return back()->with('success', 'Approval WhatsApp number removed');
    }
}

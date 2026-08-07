<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementLog;
use App\Models\User;
use App\Services\WahaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AnnouncementController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'employee')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'mobile']);

        $logs = AnnouncementLog::with('sender')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.announcements.index', compact('employees', 'logs'));
    }

    public function send(Request $request, WahaService $waha)
    {
        $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'audience' => 'required|in:all,selected',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'via_whatsapp' => 'nullable|boolean',
            'via_email' => 'nullable|boolean',
        ]);

        $viaWhatsapp = $request->boolean('via_whatsapp');
        $viaEmail = $request->boolean('via_email');

        if (!$viaWhatsapp && !$viaEmail) {
            return back()->with('error', 'Select at least one channel: WhatsApp or Email.')
                ->withInput();
        }

        if ($request->audience === 'selected' && empty($request->user_ids)) {
            return back()->with('error', 'Select at least one employee.')
                ->withInput();
        }

        $query = User::where('role', 'employee')->orderBy('name');

        if ($request->audience === 'selected') {
            $query->whereIn('id', $request->user_ids);
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No employees found for this announcement.');
        }

        $subject = $request->subject ?: 'Announcement from LSAF HR';
        $body = $request->message;

        $whatsappSent = 0;
        $whatsappFailed = 0;
        $emailSent = 0;
        $emailFailed = 0;

        foreach ($recipients as $user) {
            if ($viaWhatsapp) {
                if (!filled($user->mobile)) {
                    $whatsappFailed++;
                } else {
                    $waText = "*{$subject}*\n\n{$body}\n\n— LSAF HR";
                    if ($waha->sendToMobile($user->mobile, $waText)) {
                        $whatsappSent++;
                    } else {
                        $whatsappFailed++;
                    }
                }
            }

            if ($viaEmail) {
                if (!filled($user->email)) {
                    $emailFailed++;
                } else {
                    try {
                        Mail::raw($body."\n\n— LSAF HR", function ($message) use ($user, $subject) {
                            $message->to($user->email)->subject($subject);
                        });
                        $emailSent++;
                    } catch (\Throwable $e) {
                        $emailFailed++;
                    }
                }
            }
        }

        AnnouncementLog::create([
            'sent_by' => auth()->id(),
            'subject' => $subject,
            'message' => $body,
            'via_whatsapp' => $viaWhatsapp,
            'via_email' => $viaEmail,
            'audience' => $request->audience,
            'user_ids' => $request->audience === 'selected' ? $request->user_ids : null,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_failed' => $whatsappFailed,
            'email_sent' => $emailSent,
            'email_failed' => $emailFailed,
        ]);

        $summary = [];
        if ($viaWhatsapp) {
            $summary[] = "WhatsApp: {$whatsappSent} sent, {$whatsappFailed} failed";
        }
        if ($viaEmail) {
            $summary[] = "Email: {$emailSent} sent, {$emailFailed} failed";
        }

        return back()->with('success', 'Announcement sent. '.implode(' | ', $summary));
    }
}

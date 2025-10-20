<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BlastSchedule;
use App\Models\Contact;
use App\Models\ContactList;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Statistics
        $stats = [
            'total_accounts' => Account::where('user_id', $user->id)->count(),
            'connected_accounts' => Account::where('user_id', $user->id)
                ->where('status', 'connected')->count(),
            'total_contacts' => Contact::whereHas('contactList', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'total_lists' => ContactList::where('user_id', $user->id)->count(),
            'quota_used' => $user->message_used,
            'quota_remaining' => $user->message_quota - $user->message_used,
            'quota_percentage' => $user->message_quota > 0 
                ? round(($user->message_used / $user->message_quota) * 100, 2) 
                : 0,
        ];

        // Recent blasts
        $recentBlasts = BlastSchedule::where('user_id', $user->id)
            ->with(['template', 'account', 'contactList'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Blast statistics
        $blastStats = [
            'total' => BlastSchedule::where('user_id', $user->id)->count(),
            'scheduled' => BlastSchedule::where('user_id', $user->id)
                ->where('status', 'scheduled')->count(),
            'processing' => BlastSchedule::where('user_id', $user->id)
                ->where('status', 'processing')->count(),
            'completed' => BlastSchedule::where('user_id', $user->id)
                ->where('status', 'completed')->count(),
            'failed' => BlastSchedule::where('user_id', $user->id)
                ->where('status', 'failed')->count(),
        ];

        // Account status
        $accounts = Account::where('user_id', $user->id)
            ->latest()
            ->get();

        return view('dashboard', compact('stats', 'recentBlasts', 'blastStats', 'accounts'));
    }
}
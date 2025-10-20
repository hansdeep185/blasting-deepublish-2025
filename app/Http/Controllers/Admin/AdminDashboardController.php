<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BlastSchedule;
use App\Models\User;
use App\Models\SentMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // User statistics
        $userStats = [
            'total' => User::where('role', 'user')->count(),
            'active' => User::where('role', 'user')->where('is_active', true)->count(),
            'inactive' => User::where('role', 'user')->where('is_active', false)->count(),
        ];

        // Account statistics
        $accountStats = [
            'total' => Account::count(),
            'connected' => Account::where('status', 'connected')->count(),
            'disconnected' => Account::where('status', 'disconnected')->count(),
            'pending' => Account::where('status', 'pending')->count(),
        ];

        // Message statistics (last 30 days)
        $messageStats = [
            'total_sent' => SentMessage::where('created_at', '>=', now()->subDays(30))
                ->where('status', 'sent')->count(),
            'total_failed' => SentMessage::where('created_at', '>=', now()->subDays(30))
                ->where('status', 'failed')->count(),
            'today' => SentMessage::whereDate('created_at', today())
                ->where('status', 'sent')->count(),
        ];

        // Blast statistics
        $blastStats = [
            'total' => BlastSchedule::count(),
            'scheduled' => BlastSchedule::where('status', 'scheduled')->count(),
            'processing' => BlastSchedule::where('status', 'processing')->count(),
            'completed' => BlastSchedule::where('status', 'completed')->count(),
        ];

        // Top users by message usage
        $topUsers = User::where('role', 'user')
            ->orderBy('message_used', 'desc')
            ->limit(10)
            ->get();

        // Recent activities (blasts)
        $recentBlasts = BlastSchedule::with(['user', 'account', 'template'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Chart data - Messages per day (last 7 days)
        $messagesPerDay = SentMessage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', now()->subDays(7))
            ->where('status', 'sent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'userStats',
            'accountStats',
            'messageStats',
            'blastStats',
            'topUsers',
            'recentBlasts',
            'messagesPerDay'
        ));
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\WahaApiService;
use Illuminate\Console\Command;

class EnableNowebStore extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'waha:enable-noweb 
                            {session? : Specific session name to update}
                            {--all : Update all connected sessions}';

    /**
     * The console command description.
     */
    protected $description = 'Enable NOWEB store for WAHA sessions to fix 400 errors';

    protected WahaApiService $wahaService;

    public function __construct(WahaApiService $wahaService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Enabling NOWEB store for WAHA sessions...');
        $this->newLine();

        if ($this->option('all')) {
            return $this->enableForAllSessions();
        }

        if ($sessionName = $this->argument('session')) {
            return $this->enableForSession($sessionName);
        }

        // Interactive mode
        return $this->interactiveMode();
    }

    /**
     * Enable NOWEB for all connected sessions
     */
    protected function enableForAllSessions(): int
    {
        $accounts = Account::where('status', 'connected')->get();

        if ($accounts->isEmpty()) {
            $this->error('❌ No connected accounts found!');
            return Command::FAILURE;
        }

        $this->info("Found {$accounts->count()} connected session(s)");
        $this->newLine();

        $success = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            $this->info("Processing: {$account->session_name} ({$account->name})");

            if ($this->updateSession($account->session_name)) {
                $success++;
            } else {
                $failed++;
            }

            $this->newLine();
        }

        $this->info("✅ Successfully updated: {$success}");
        if ($failed > 0) {
            $this->warn("⚠️  Failed: {$failed}");
        }

        return Command::SUCCESS;
    }

    /**
     * Enable NOWEB for specific session
     */
    protected function enableForSession(string $sessionName): int
    {
        $account = Account::where('session_name', $sessionName)->first();

        if (!$account) {
            $this->error("❌ Session '{$sessionName}' not found!");
            return Command::FAILURE;
        }

        $this->info("Processing: {$sessionName} ({$account->name})");
        $this->newLine();

        if ($this->updateSession($sessionName)) {
            $this->info('✅ NOWEB store enabled successfully!');
            return Command::SUCCESS;
        }

        $this->error('❌ Failed to enable NOWEB store');
        return Command::FAILURE;
    }

    /**
     * Interactive mode - let user choose
     */
    protected function interactiveMode(): int
    {
        $accounts = Account::where('status', 'connected')->get();

        if ($accounts->isEmpty()) {
            $this->error('❌ No connected accounts found!');
            return Command::FAILURE;
        }

        $choices = $accounts->mapWithKeys(function ($account) {
            return [$account->session_name => "{$account->name} ({$account->session_name})"];
        })->toArray();

        $choices['all'] = '🔄 Update all sessions';

        $selected = $this->choice(
            'Select session to enable NOWEB store:',
            $choices,
            'all'
        );

        if ($selected === '🔄 Update all sessions') {
            return $this->enableForAllSessions();
        }

        return $this->enableForSession($selected);
    }

    /**
     * Update single session
     */
    protected function updateSession(string $sessionName): bool
    {
        // Step 1: Update config
        $this->line("  → Updating session config...");
        $result = $this->wahaService->updateSessionConfig($sessionName);

        if (!$result['success']) {
            $this->error("  ✗ Failed to update config: " . ($result['error'] ?? 'Unknown error'));
            return false;
        }

        $this->info("  ✓ Config updated");

        // Step 2: Restart session to apply changes
        $this->line("  → Restarting session...");
        $restartResult = $this->wahaService->restartSession($sessionName);

        if (!$restartResult['success']) {
            $this->warn("  ⚠ Failed to restart: " . ($restartResult['error'] ?? 'Unknown error'));
            $this->warn("  → Please restart manually or scan QR again");
            return true; // Config is updated, restart can be manual
        }

        $this->info("  ✓ Session restarted");

        // Step 3: Verify
        $this->line("  → Verifying NOWEB store...");
        sleep(3); // Wait for session to be ready

        $testResult = $this->wahaService->getChatsOverview($sessionName, 1);

        if ($testResult['success']) {
            $this->info("  ✓ NOWEB store is working!");
            return true;
        }

        $this->warn("  ⚠ Verification failed, but config is updated");
        $this->warn("  → May need to scan QR code again");

        return true;
    }
}
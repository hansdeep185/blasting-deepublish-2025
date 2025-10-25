<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BlastSchedule;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\ContactTag;
use App\Models\SentMessage; // DIUBAH: Menggunakan SentMessage secara eksplisit
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BlastScheduleController extends Controller
{
    /**
     * Display a listing of blast campaigns
     */
    public function index(Request $request)
    {
        $query = BlastSchedule::where('user_id', auth()->id())
            ->with(['account', 'template']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $blasts = $query->latest()->paginate(15);

        // Stats
        $stats = [
            'total' => BlastSchedule::where('user_id', auth()->id())->count(),
            'pending' => BlastSchedule::where('user_id', auth()->id())->where('status', 'pending')->count(),
            'processing' => BlastSchedule::where('user_id', auth()->id())->where('status', 'processing')->count(),
            'completed' => BlastSchedule::where('user_id', auth()->id())->where('status', 'completed')->count(),
        ];

        return view('blasts.index', compact('blasts', 'stats'));
    }

    /**
     * Show the form for creating a new blast campaign
     */
    public function create()
    {
        // Get user's active WA accounts
        $accounts = Account::where('user_id', auth()->id())
            ->where('status', 'connected')
            ->get();

        if ($accounts->isEmpty()) {
            return redirect()
                ->route('accounts.index')
                ->with('error', 'Please connect a WhatsApp account first!');
        }

        // Get templates
        $templates = Template::where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get contact lists
        $contactLists = ContactList::where('user_id', auth()->id())
            ->withCount('contacts')
            ->get();

        // Get tags (groups)
        $contactTags = ContactTag::whereHas('contactList', function($q) {
            $q->where('user_id', auth()->id());
        })
        ->withCount('contacts')
        ->get();

        return view('blasts.create', compact('accounts', 'templates', 'contactLists', 'contactTags'));
    }

    /**
     * Store a newly created blast campaign
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'account_id' => 'required|exists:accounts,id',
            'template_id' => 'required|exists:templates,id',
            'target_type' => 'required|in:all,contact_list,contact_group,selected_contacts',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
            'schedule_type' => 'required|in:immediate,scheduled',
            'scheduled_at' => 'required_if:schedule_type,scheduled|nullable|date|after:now',
        ]);

        // Check account ownership
        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== auth()->id()) {
            abort(403);
        }

        // Check template ownership
        $template = Template::findOrFail($validated['template_id']);
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        DB::beginTransaction();
        try {
             // Prepare data for creation
            $data = [
                'user_id' => auth()->id(),
                'account_id' => $validated['account_id'],
                'template_id' => $validated['template_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'target_type' => $validated['target_type'],
                'target_ids' => $validated['target_ids'] ?? null,
                'schedule_type' => $validated['schedule_type'],
                'scheduled_at' => $validated['schedule_type'] === 'scheduled' ? $validated['scheduled_at'] : null,
                'status' => 'draft',
            ];

            // Mengisi contact_list_id untuk memenuhi syarat NOT NULL
            if ($validated['target_type'] === 'contact_list' && !empty($validated['target_ids'])) {
                $data['contact_list_id'] = $validated['target_ids'][0];
            } else {
                $firstList = ContactList::where('user_id', auth()->id())->first();
                if (!$firstList) {
                    DB::rollBack();
                    return back()->with('error', 'You must have at least one contact list to create a campaign.');
                }
                $data['contact_list_id'] = $firstList->id;
            }

            // Create blast schedule with the prepared data
            $blast = BlastSchedule::create($data);

            // Get target contacts
            $contacts = $blast->getTargetContacts();

            if ($contacts->isEmpty()) {
                DB::rollBack();
                return back()->with('error', 'No contacts found for the selected target!');
            }

            // Create blast messages for each contact
            foreach ($contacts as $contact) {
                // Render message with contact data
                $messageContent = $template->render($contact->getTemplateData());

                // =========================================================================
                // AWAL PERBAIKAN: Menggunakan $contact->phone_number
                // =========================================================================
                SentMessage::create([
                    'blast_schedule_id' => $blast->id,
                    'contact_id' => $contact->id,
                    'phone_number' => $contact->phone_number, // DIUBAH: dari $contact->phone menjadi $contact->phone_number
                    'recipient_name' => $contact->name,
                    'message_content' => $messageContent,
                    'status' => 'pending',
                ]);
                // =========================================================================
                // AKHIR PERBAIKAN
                // =========================================================================
            }

            // Update statistics
            $blast->calculateStats();

            // Update status to pending (ready to process)
            $blast->update(['status' => 'pending']);

            // Increment template usage
            // $template->incrementUsage();

            DB::commit();

            // If immediate, dispatch to queue
            if ($validated['schedule_type'] === 'immediate') {
                // TODO: Dispatch job to queue
                // dispatch(new ProcessBlastCampaign($blast));
            }

            return redirect()
                ->route('blasts.show', $blast)
                ->with('success', 'Blast campaign created successfully! ' . ($validated['schedule_type'] === 'immediate' ? 'Processing will start shortly.' : 'Scheduled for ' . $validated['scheduled_at']));

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create blast campaign: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified blast campaign
     */
    public function show(BlastSchedule $blast)
    {
        // Check authorization
        if ($blast->user_id !== auth()->id()) {
            abort(403);
        }

        $blast->load(['account', 'template', 'messages' => function($query) {
            $query->latest()->limit(50);
        }]);

        // Refresh stats
        $blast->calculateStats();

        return view('blasts.show', compact('blast'));
    }

    /**
     * Cancel blast campaign
     */
    public function cancel(BlastSchedule $blast)
    {
        // Check authorization
        if ($blast->user_id !== auth()->id()) {
            abort(403);
        }

        if (!$blast->canBeCancelled()) {
            return back()->with('error', 'This campaign cannot be cancelled!');
        }

        $blast->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Blast campaign cancelled successfully!');
    }

    /**
     * View campaign report
     */
    public function report(Request $request, BlastSchedule $blast)
    {
        // Check authorization
        if ($blast->user_id !== auth()->id()) {
            abort(403);
        }

        $blast->load(['account', 'template']);

        // Get message statistics
        $messageStats = [
            'total' => $blast->total_recipients,
            'sent' => $blast->messages()->sent()->count(),
            'failed' => $blast->messages()->failed()->count(),
            'pending' => $blast->messages()->whereIn('status', ['pending', 'queued'])->count(),
        ];

        // Query dasar untuk pesan
        $query = $blast->messages()->with('contact')->latest();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by phone or name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate(20)->withQueryString();

        return view('blasts.report', compact('blast', 'messageStats', 'messages'));
    }

    /**
     * Preview campaign before sending
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:templates,id',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $template = Template::findOrFail($validated['template_id']);

        // Check template ownership
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        // Get contact or use sample data
        if (isset($validated['contact_id'])) {
            $contact = Contact::findOrFail($validated['contact_id']);
            $data = $contact->getTemplateData();
        } else {
            // Sample data
            $data = [
                'name' => 'John Doe',
                'phone' => '08123456789',
                'email' => 'john@example.com',
                'company' => 'PT Example',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date' => now()->format('d M Y'),
                'time' => now()->format('H:i'),
            ];
        }

        $preview = $template->render($data);

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'data' => $data,
        ]);
    }
}


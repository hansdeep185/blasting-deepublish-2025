<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ContactList;
use Illuminate\Http\Request;

class ContactListController extends Controller
{
    /**
     * Display a listing of contact lists
     */
    public function index()
    {
        $contactLists = ContactList::where('user_id', auth()->id())
            ->withCount('contacts')
            ->latest()
            ->paginate(15);

        $stats = [
            'total_lists' => ContactList::where('user_id', auth()->id())->count(),
            'total_contacts' => ContactList::where('user_id', auth()->id())
                ->withCount('contacts')
                ->get()
                ->sum('contacts_count'),
        ];

        return view('contact-lists.index', compact('contactLists', 'stats'));
    }

    /**
     * Show the form for creating a new contact list
     */
    public function create()
    {
        return view('contact-lists.create');
    }

    /**
     * Store a newly created contact list
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        // Process tags
        $tags = [];
        if ($request->tags) {
            $tags = array_map('trim', explode(',', $request->tags));
        }

        $contactList = ContactList::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $tags,
            'total_contacts' => 0,
        ]);

        AuditLog::logActivity(
            action: 'create_contact_list',
            description: "Created contact list: {$contactList->name}",
            modelType: ContactList::class,
            modelId: $contactList->id
        );

        return redirect()->route('contact-lists.show', $contactList)
            ->with('success', 'Contact list created successfully!');
    }

    /**
     * Display the specified contact list
     */
    public function show(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $contactList->load('contacts');

        return view('contact-lists.show', compact('contactList'));
    }

    /**
     * Show the form for editing the specified contact list
     */
    public function edit(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        return view('contact-lists.edit', compact('contactList'));
    }

    /**
     * Update the specified contact list
     */
    public function update(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'tags' => 'nullable|string',
        ]);

        // Process tags
        $tags = [];
        if ($request->tags) {
            $tags = array_map('trim', explode(',', $request->tags));
        }

        $contactList->update([
            'name' => $request->name,
            'description' => $request->description,
            'tags' => $tags,
        ]);

        AuditLog::logActivity(
            action: 'update_contact_list',
            description: "Updated contact list: {$contactList->name}",
            modelType: ContactList::class,
            modelId: $contactList->id
        );

        return redirect()->route('contact-lists.show', $contactList)
            ->with('success', 'Contact list updated successfully!');
    }

    /**
     * Remove the specified contact list
     */
    public function destroy(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        AuditLog::logActivity(
            action: 'delete_contact_list',
            description: "Deleted contact list: {$contactList->name}",
            modelType: ContactList::class,
            modelId: $contactList->id
        );

        $contactList->delete();

        return redirect()->route('contact-lists.index')
            ->with('success', 'Contact list deleted successfully!');
    }
}
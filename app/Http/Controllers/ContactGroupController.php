<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactGroupController extends Controller
{
    /**
     * Display a listing of contact groups
     */
    public function index()
    {
        $groups = ContactGroup::forUser(Auth::id())
            ->withCount('contacts')
            ->latest()
            ->paginate(10);

        return view('contact-groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group
     */
    public function create()
    {
        return view('contact-groups.create');
    }

    /**
     * Store a newly created group
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $group = ContactGroup::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'contact_count' => 0,
        ]);

        return redirect()
            ->route('contact-groups.show', $group)
            ->with('success', 'Contact group created successfully!');
    }

    /**
     * Display the specified group
     */
    public function show(ContactGroup $contactGroup)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $contactGroup->load('contacts');
        
        // Get contacts not in this group (for adding)
        $availableContacts = Contact::forUser(Auth::id())
            ->whereDoesntHave('groups', function($query) use ($contactGroup) {
                $query->where('contact_groups.id', $contactGroup->id);
            })
            ->get();

        return view('contact-groups.show', compact('contactGroup', 'availableContacts'));
    }

    /**
     * Show the form for editing the specified group
     */
    public function edit(ContactGroup $contactGroup)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('contact-groups.edit', compact('contactGroup'));
    }

    /**
     * Update the specified group
     */
    public function update(Request $request, ContactGroup $contactGroup)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $contactGroup->update($validated);

        return redirect()
            ->route('contact-groups.show', $contactGroup)
            ->with('success', 'Contact group updated successfully!');
    }

    /**
     * Remove the specified group
     */
    public function destroy(ContactGroup $contactGroup)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $contactGroup->delete();

        return redirect()
            ->route('contact-groups.index')
            ->with('success', 'Contact group deleted successfully!');
    }

    /**
     * Add contacts to group
     */
    public function addContacts(Request $request, ContactGroup $contactGroup)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        // Verify all contacts belong to user
        $contacts = Contact::whereIn('id', $validated['contact_ids'])
            ->forUser(Auth::id())
            ->get();

        $contactGroup->contacts()->syncWithoutDetaching($contacts->pluck('id'));
        $contactGroup->updateContactCount();

        return redirect()
            ->route('contact-groups.show', $contactGroup)
            ->with('success', count($contacts) . ' contact(s) added to group!');
    }

    /**
     * Remove contact from group
     */
    public function removeContact(ContactGroup $contactGroup, Contact $contact)
    {
        // Authorization check
        if ($contactGroup->user_id !== Auth::id() || $contact->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $contactGroup->contacts()->detach($contact->id);
        $contactGroup->updateContactCount();

        return redirect()
            ->route('contact-groups.show', $contactGroup)
            ->with('success', 'Contact removed from group!');
    }
}
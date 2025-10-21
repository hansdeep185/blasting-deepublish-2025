<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\ContactTag;
use Illuminate\Http\Request;

class ContactTagController extends Controller
{
    /**
     * Display tags for a contact list
     */
    public function index(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $tags = $contactList->contactTags()->withCount('contacts')->get();

        return view('contact-tags.index', compact('contactList', 'tags'));
    }

    /**
     * Store a new tag
     */
    public function store(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        $contactList->contactTags()->create([
            'name' => $request->name,
            'color' => $request->color ?? '#6c757d',
            'description' => $request->description,
        ]);

        return back()->with('success', 'Tag created successfully!');
    }

    /**
     * Update tag
     */
    public function update(Request $request, ContactList $contactList, ContactTag $contactTag)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() || $contactTag->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'description' => 'nullable|string|max:500',
        ]);

        $contactTag->update([
            'name' => $request->name,
            'color' => $request->color ?? $contactTag->color,
            'description' => $request->description,
        ]);

        return back()->with('success', 'Tag updated successfully!');
    }

    /**
     * Delete tag
     */
    public function destroy(ContactList $contactList, ContactTag $contactTag)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() || $contactTag->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contactTag->delete();

        return back()->with('success', 'Tag deleted successfully!');
    }

    /**
     * Bulk tag contacts
     */
    public function bulkTag(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
            'tag_id' => 'required|exists:contact_tags,id',
        ]);

        $tag = ContactTag::find($request->tag_id);
        
        // Verify tag belongs to this contact list
        if ($tag->contact_list_id !== $contactList->id) {
            abort(403);
        }

        // Get contacts
        $contacts = Contact::whereIn('id', $request->contact_ids)
            ->where('contact_list_id', $contactList->id)
            ->get();

        // Attach tag to contacts
        foreach ($contacts as $contact) {
            $contact->tags()->syncWithoutDetaching([$tag->id]);
        }

        return back()->with('success', count($contacts) . ' contact(s) tagged successfully!');
    }

    /**
     * Remove tag from contact
     */
    public function removeTag(ContactList $contactList, Contact $contact, ContactTag $contactTag)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() 
            || $contact->contact_list_id !== $contactList->id
            || $contactTag->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->tags()->detach($contactTag->id);

        return back()->with('success', 'Tag removed from contact!');
    }
}
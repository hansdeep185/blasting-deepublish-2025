<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\ContactTag;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContactsImport;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts
     */
    public function index(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $query = $contactList->contacts()->with('tags');

        // Filter by tag
        if ($request->filled('tag_id')) {
            $query->withTag($request->tag_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $contacts = $query->latest()->paginate(50);
        $tags = $contactList->contactTags;

        return view('contacts.index', compact('contactList', 'contacts', 'tags'));
    }

    /**
     * Show the form for creating a new contact
     */
    public function create(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $tags = $contactList->tags;
        return view('contacts.create', compact('contactList', 'tags'));
    }

    /**
     * Store a newly created contact
     */
    public function store(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'phone_number' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:contact_tags,id',
        ]);

        // Format phone number (remove non-numeric)
        $phoneNumber = preg_replace('/[^0-9]/', '', $request->phone_number);
        
        // Check for duplicates
        $exists = $contactList->contacts()
            ->where('phone_number', $phoneNumber)
            ->exists();

        if ($exists) {
            return back()->with('error', 'This phone number already exists in this list!');
        }

        $contact = $contactList->contacts()->create([
            'phone_number' => $phoneNumber,
            'name' => $request->name,
            'email' => $request->email,
            'custom_fields' => [],
        ]);

        // Attach tags
        if ($request->filled('tag_ids')) {
            $contact->tags()->attach($request->tag_ids);
        }

        // Update contact count
        $contactList->updateContactCount();

        AuditLog::logActivity(
            action: 'create_contact',
            description: "Added contact: {$contact->name} to list: {$contactList->name}",
            modelType: Contact::class,
            modelId: $contact->id
        );

        return redirect()->route('contacts.index', $contactList)
            ->with('success', 'Contact added successfully!');
    }

    /**
     * Show import form
     */
    public function importForm(ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $tags = $contactList->tags;
        return view('contacts.import', compact('contactList', 'tags'));
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $filename = 'contact_import_template.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['phone_number', 'name', 'email']);
            
            // Add sample data
            fputcsv($file, ['08123456789', 'John Doe', 'john@example.com']);
            fputcsv($file, ['08987654321', 'Jane Smith', 'jane@example.com']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import contacts from file
     */
    public function import(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // Max 10MB
            'tag_id' => 'nullable|exists:contact_tags,id',
        ]);

        try {
            $file = $request->file('file');
            $tagId = $request->tag_id;
            
            // Read file
            $data = Excel::toArray(new ContactsImport, $file);
            
            if (empty($data) || empty($data[0])) {
                return back()->with('error', 'File is empty or invalid format!');
            }

            $rows = $data[0];
            $header = array_shift($rows); // Remove header row
            
            // Normalize headers
            $header = array_map('strtolower', array_map('trim', $header));
            
            $imported = 0;
            $duplicates = 0;
            $errors = 0;

            foreach ($rows as $row) {
                if (empty($row[0])) continue; // Skip empty rows
                
                $rowData = array_combine($header, $row);
                
                // Get phone number (try different column names)
                $phoneNumber = $rowData['phone'] 
                    ?? $rowData['phone_number'] 
                    ?? $rowData['number'] 
                    ?? $rowData['no'] 
                    ?? null;

                if (!$phoneNumber) {
                    $errors++;
                    continue;
                }

                // Clean phone number
                $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
                
                // Check duplicate
                $exists = $contactList->contacts()
                    ->where('phone_number', $phoneNumber)
                    ->exists();

                if ($exists) {
                    $duplicates++;
                    continue;
                }

                // Create contact
                $contact = $contactList->contacts()->create([
                    'phone_number' => $phoneNumber,
                    'name' => $rowData['name'] ?? $rowData['nama'] ?? null,
                    'email' => $rowData['email'] ?? null,
                    'custom_fields' => $rowData,
                ]);

                // Attach tag if specified
                if ($tagId) {
                    $contact->tags()->attach($tagId);
                }

                $imported++;
            }

            // Update contact count
            $contactList->updateContactCount();

            AuditLog::logActivity(
                action: 'import_contacts',
                description: "Imported {$imported} contacts to list: {$contactList->name}",
                modelType: ContactList::class,
                modelId: $contactList->id
            );

            $message = "Import completed! Imported: {$imported}";
            if ($duplicates > 0) $message .= ", Duplicates: {$duplicates}";
            if ($errors > 0) $message .= ", Errors: {$errors}";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified contact
     */
    public function edit(ContactList $contactList, Contact $contact)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $tags = $contactList->tags;
        $contact->load('tags');
        
        return view('contacts.edit', compact('contactList', 'contact', 'tags'));
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, ContactList $contactList, Contact $contact)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $request->validate([
            'phone_number' => 'required|string|max:20',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:contact_tags,id',
        ]);

        $phoneNumber = preg_replace('/[^0-9]/', '', $request->phone_number);

        $contact->update([
            'phone_number' => $phoneNumber,
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Sync tags
        $contact->tags()->sync($request->tag_ids ?? []);

        return redirect()->route('contacts.index', $contactList)
            ->with('success', 'Contact updated successfully!');
    }

    /**
     * Remove the specified contact
     */
    public function destroy(ContactList $contactList, Contact $contact)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->delete();
        
        // Update contact count
        $contactList->updateContactCount();

        return back()->with('success', 'Contact deleted successfully!');
    }

    /**
     * Bulk delete contacts
     */
    public function bulkDelete(Request $request, ContactList $contactList)
    {
        // Authorization check
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $deleted = Contact::whereIn('id', $request->contact_ids)
            ->where('contact_list_id', $contactList->id)
            ->delete();

        // Update contact count
        $contactList->updateContactCount();

        return back()->with('success', "{$deleted} contact(s) deleted successfully!");
    }
}
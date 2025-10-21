<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\ContactTag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of contacts in a contact list
     */
    public function index(ContactList $contactList)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        // Get contacts with pagination
        $query = $contactList->contacts()->with('tags');

        // Search
        if (request()->filled('search')) {
            $search = request('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Filter by tag
        if (request()->filled('tag_id')) {
            $query->whereHas('tags', function($q) {
                $q->where('contact_tags.id', request('tag_id'));
            });
        }

        // Filter by status
        if (request()->filled('status')) {
            $query->where('is_active', request('status') === 'active');
        }

        $contacts = $query->latest()->paginate(20);

        // Get all tags for filter
        $tags = $contactList->tags()->orderBy('name')->get();

        // Stats
        $stats = [
            'total' => $contactList->contacts()->count(),
            'active' => $contactList->contacts()->where('is_active', true)->count(),
            'with_email' => $contactList->contacts()->whereNotNull('email')->where('email', '!=', '')->count(),
            'tagged' => $contactList->contacts()->has('tags')->count(),
        ];

        return view('contacts.index', compact('contactList', 'contacts', 'tags', 'stats'));
    }

    /**
     * Show the form for creating a new contact
     */
    public function create(ContactList $contactList)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        $tags = $contactList->tags()->orderBy('name')->get();

        return view('contacts.create', compact('contactList', 'tags'));
    }
    
    /**
     * Show the form for importing contacts
     */
    public function importForm(ContactList $contactList)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }
        
        $tags = $contactList->tags()->orderBy('name')->get();

        return view('contacts.import', compact('contactList', 'tags'));
    }

    /**
     * Store a newly created contact
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_list_id' => 'required|exists:contact_lists,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:contact_tags,id',
            'custom_fields' => 'nullable|array',
        ]);

        $contactList = ContactList::findOrFail($validated['contact_list_id']);

        // Check authorization
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        // Create contact
        $contact = Contact::create([
            'contact_list_id' => $contactList->id,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? '',
            'email' => $validated['email'] ?? '',
            'company' => $validated['company'] ?? '',
            'notes' => $validated['notes'] ?? '',
            'custom_fields' => $validated['custom_fields'] ?? null,
            'is_active' => true,
        ]);

        // Attach tags
        if (!empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        return redirect()
            ->route('contacts.index', $contactList) // FIX: Menggunakan nama route yang benar
            ->with('success', 'Contact created successfully!');
    }

    /**
     * Display the specified contact
     */
    public function show(ContactList $contactList, Contact $contact)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->load('tags');

        return view('contacts.show', compact('contactList', 'contact'));
    }

    /**
     * Show the form for editing the specified contact
     */
    public function edit(ContactList $contactList, Contact $contact)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->load('tags');
        $tags = $contactList->tags()->orderBy('name')->get();

        return view('contacts.edit', compact('contactList', 'contact', 'tags'));
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, ContactList $contactList, Contact $contact)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:contact_tags,id',
            'custom_fields' => 'nullable|array',
        ]);

        $contact->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? '',
            'email' => $validated['email'] ?? '',
            'company' => $validated['company'] ?? '',
            'notes' => $validated['notes'] ?? '',
            'custom_fields' => $validated['custom_fields'] ?? null,
        ]);

        // Sync tags
        if (isset($validated['tag_ids'])) {
            $contact->tags()->sync($validated['tag_ids']);
        } else {
            $contact->tags()->detach();
        }

        return redirect()
            ->route('contacts.index', $contactList) // FIX: Menggunakan nama route yang benar
            ->with('success', 'Contact updated successfully!');
    }

    /**
     * Remove the specified contact
     */
    public function destroy(ContactList $contactList, Contact $contact)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->delete();

        return redirect()
            ->route('contacts.index', $contactList) // FIX: Menggunakan nama route yang benar
            ->with('success', 'Contact deleted successfully!');
    }

    /**
     * Import contacts from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'contact_list_id' => 'required|exists:contact_lists,id',
            'csv_file' => 'required|file|mimes:csv,txt',
            'has_header' => 'boolean',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:contact_tags,id',
        ]);

        $contactList = ContactList::findOrFail($request->contact_list_id);

        // Check authorization
        if ($contactList->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $file = $request->file('csv_file');
            $hasHeader = $request->boolean('has_header', true);
            
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            
            $headers = $hasHeader ? array_shift($csvData) : [];
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);
            
            $standardColumns = ['name', 'phone', 'email', 'company', 'notes'];
            
            $customFieldColumns = [];
            foreach ($headers as $index => $header) {
                if (!in_array($header, $standardColumns)) {
                    $customFieldColumns[$index] = $header;
                }
            }
            
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($csvData as $rowIndex => $row) {
                try {
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    $data = [];
                    $customFields = [];
                    
                    foreach ($row as $index => $value) {
                        $value = trim($value);
                        
                        if ($hasHeader && isset($headers[$index])) {
                            $header = $headers[$index];
                            
                            if (in_array($header, $standardColumns)) {
                                $data[$header] = $value;
                            }
                            else if (isset($customFieldColumns[$index])) {
                                $customFields[$customFieldColumns[$index]] = $value;
                            }
                        } else {
                            if ($index == 0) $data['name'] = $value;
                            if ($index == 1) $data['phone'] = $value;
                            if ($index == 2) $data['email'] = $value;
                            if ($index == 3) $data['company'] = $value;
                            if ($index >= 4) {
                                $customFields["custom_field_" . ($index - 3)] = $value;
                            }
                        }
                    }

                    if (empty($data['name']) && empty($data['phone'])) {
                        $skipped++;
                        $errors[] = "Row " . ($rowIndex + 2) . ": Missing name and phone";
                        continue;
                    }

                    if (empty($data['name'])) {
                        $data['name'] = $data['phone'] ?? 'Unknown';
                    }

                    $contact = Contact::create([
                        'contact_list_id' => $contactList->id,
                        'name' => $data['name'] ?? '',
                        'phone' => $data['phone'] ?? '',
                        'email' => $data['email'] ?? '',
                        'company' => $data['company'] ?? '',
                        'notes' => $data['notes'] ?? '',
                        'custom_fields' => !empty($customFields) ? $customFields : null,
                        'is_active' => true,
                    ]);

                    if ($request->has('tag_ids') && !empty($request->tag_ids)) {
                        $contact->tags()->attach($request->tag_ids);
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $skipped++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }

            $message = "Imported {$imported} contacts successfully.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} rows.";
            }

            if (!empty($errors)) {
                session()->flash('import_errors', $errors);
            }

            return redirect()
                ->route('contacts.index', $contactList) // FIX: Menggunakan nama route yang benar
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download CSV template with custom field examples
     */
    public function downloadTemplate()
    {
        $csv = "name,phone,email,company,notes,discount,product_interest,birthday,city\n";
        $csv .= "John Doe,08123456789,john@example.com,PT Example,VIP Customer,10,Laptop,1990-01-15,Jakarta\n";
        $csv .= "Jane Smith,08234567890,jane@example.com,ABC Corp,New customer,5,Smartphone,1985-05-20,Bandung\n";
        $csv .= "Bob Wilson,08345678901,bob@example.com,XYZ Ltd,,15,Tablet,1992-08-10,Surabaya\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="contacts_template_with_custom_fields.csv"');
    }

    /**
     * Bulk delete contacts
     */
    public function bulkDelete(Request $request, ContactList $contactList)
    {
        // Check authorization
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

        return back()->with('success', "{$deleted} contacts deleted successfully!");
    }

    /**
     * Toggle contact status
     */
    public function toggleStatus(ContactList $contactList, Contact $contact)
    {
        // Check authorization
        if ($contactList->user_id !== auth()->id() || $contact->contact_list_id !== $contactList->id) {
            abort(403);
        }

        $contact->update(['is_active' => !$contact->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $contact->is_active,
            'message' => 'Contact status updated successfully!',
        ]);
    }
}


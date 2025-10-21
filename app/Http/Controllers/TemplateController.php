<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    /**
     * Display a listing of templates
     */
    public function index(Request $request)
    {
        $query = Template::where('user_id', auth()->id());

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Filter active/inactive
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $templates = $query->latest()->paginate(12);

        // Stats
        $stats = [
            'total' => Template::where('user_id', auth()->id())->count(),
            'active' => Template::where('user_id', auth()->id())->where('is_active', true)->count(),
            'marketing' => Template::where('user_id', auth()->id())->where('category', 'marketing')->count(),
            'notification' => Template::where('user_id', auth()->id())->where('category', 'notification')->count(),
        ];

        return view('templates.index', compact('templates', 'stats'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        $availableVariables = Template::getAvailableVariables();
        return view('templates.create', compact('availableVariables'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:marketing,notification,reminder,greeting,other',
            'description' => 'nullable|string|max:500',
            'media_type' => 'nullable|in:image,document,video',
            'media_url' => 'nullable|string|max:500',
        ]);

        $template = Template::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'media_type' => $validated['media_type'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'variables' => (new Template(['content' => $validated['content']]))->extractVariables(),
        ]);

        return redirect()
            ->route('templates.show', $template)
            ->with('success', 'Template created successfully!');
    }

    /**
     * Display the specified template
     */
    public function show(Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->load('blastSchedules');
        $availableVariables = Template::getAvailableVariables();
        
        // Sample data for preview
        $sampleData = [
            'name' => 'John Doe',
            'phone' => '08123456789',
            'email' => 'john@example.com',
            'company' => 'PT Example',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date' => now()->format('d M Y'),
            'time' => now()->format('H:i'),
        ];

        $preview = $template->render($sampleData);

        return view('templates.show', compact('template', 'availableVariables', 'preview', 'sampleData'));
    }

    /**
     * Show the form for editing the specified template
     */
    public function edit(Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $availableVariables = Template::getAvailableVariables();
        return view('templates.edit', compact('template', 'availableVariables'));
    }

    /**
     * Update the specified template
     */
    public function update(Request $request, Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|in:marketing,notification,reminder,greeting,other',
            'description' => 'nullable|string|max:500',
            'media_type' => 'nullable|in:image,document,video',
            'media_url' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $template->update([
            'name' => $validated['name'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'media_type' => $validated['media_type'] ?? null,
            'media_url' => $validated['media_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'variables' => (new Template(['content' => $validated['content']]))->extractVariables(),
        ]);

        return redirect()
            ->route('templates.show', $template)
            ->with('success', 'Template updated successfully!');
    }

    /**
     * Remove the specified template
     */
    public function destroy(Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        // Check if template is being used
        if ($template->blastSchedules()->count() > 0) {
            return back()->with('error', 'Cannot delete template that is being used in blast campaigns!');
        }

        $template->delete();

        return redirect()
            ->route('templates.index')
            ->with('success', 'Template deleted successfully!');
    }

    /**
     * Duplicate template
     */
    public function duplicate(Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->usage_count = 0;
        $newTemplate->last_used_at = null;
        $newTemplate->save();

        return redirect()
            ->route('templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully!');
    }

    /**
     * Toggle template status
     */
    public function toggleStatus(Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $template->update(['is_active' => !$template->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $template->is_active,
            'message' => 'Template status updated successfully!',
        ]);
    }

    /**
     * Preview template with custom data
     */
    public function preview(Request $request, Template $template)
    {
        // Authorization check
        if ($template->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->all();
        $preview = $template->render($data);

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }
}
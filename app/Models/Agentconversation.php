<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_agent_id',
        'account_id',
        'chat_id',
        'contact_number',
        'contact_name',
        'status',
        'handed_over_at',
        'handover_reason',
        'labels',
        'message_count',
        'last_message_at',
        'started_at',
    ];

    protected $casts = [
        'labels' => 'array',
        'handed_over_at' => 'datetime',
        'last_message_at' => 'datetime',
        'started_at' => 'datetime',
        'message_count' => 'integer',
    ];

    // Relationships
    public function aiAgent()
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeHandedOver($query)
    {
        return $query->where('status', 'handed_over');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // Methods
    public function incrementMessageCount()
    {
        $this->increment('message_count');
        $this->update(['last_message_at' => now()]);
    }

    public function handOver($reason = null)
    {
        $this->update([
            'status' => 'handed_over',
            'handed_over_at' => now(),
            'handover_reason' => $reason,
        ]);

        // Increment agent's handover count
        $this->aiAgent->incrementStats('handover_count');
    }

    public function close()
    {
        $this->update(['status' => 'closed']);
    }

    public function addLabel($labelId)
    {
        $labels = $this->labels ?? [];
        
        if (!in_array($labelId, $labels)) {
            $labels[] = $labelId;
            $this->update(['labels' => $labels]);
            
            // Increment label usage
            ConversationLabel::find($labelId)?->incrementUsage();
        }
    }

    public function removeLabel($labelId)
    {
        $labels = $this->labels ?? [];
        $labels = array_filter($labels, fn($id) => $id != $labelId);
        $this->update(['labels' => array_values($labels)]);
    }

    public function getLabelsModels()
    {
        if (!$this->labels || empty($this->labels)) {
            return collect();
        }

        return ConversationLabel::whereIn('id', $this->labels)->get();
    }

    public function hasLabel($labelId)
    {
        return in_array($labelId, $this->labels ?? []);
    }
}
<div class="d-flex {{ $message->direction === 'outgoing' ? 'justify-content-end' : 'justify-content-start' }} mb-2">
    <div class="message-bubble {{ $message->direction === 'outgoing' ? 'bg-success text-white' : 'bg-white' }} rounded px-3 py-2 shadow-sm" 
         style="max-width: 70%;">
        
        {{-- Media Content --}}
        @if($message->media_url)
            <div class="mb-2">
                @if($message->media_type === 'image')
                    <img src="{{ $message->media_url }}" class="img-fluid rounded" alt="Image" style="max-width: 300px;">
                @elseif($message->media_type === 'video')
                    <video controls class="img-fluid rounded" style="max-width: 300px;">
                        <source src="{{ $message->media_url }}">
                    </video>
                @elseif($message->media_type === 'document')
                    <a href="{{ $message->media_url }}" target="_blank" class="text-decoration-none {{ $message->direction === 'outgoing' ? 'text-white' : 'text-primary' }}">
                        <i class="bi bi-file-earmark me-2"></i>
                        {{ $message->media_caption ?? 'Document' }}
                    </a>
                @elseif($message->media_type === 'audio')
                    <audio controls class="w-100">
                        <source src="{{ $message->media_url }}">
                    </audio>
                @endif
            </div>
        @endif
        
        {{-- Message Text --}}
        @if($message->message_content && $message->media_type !== 'document')
            <div style="white-space: pre-wrap; word-break: break-word;">
                {{ $message->message_content }}
            </div>
        @endif
        
        {{-- Timestamp & Status --}}
        <div class="text-end mt-1">
            <small class="{{ $message->direction === 'outgoing' ? 'text-white-50' : 'text-muted' }}" style="font-size: 0.75rem;">
                {{ $message->created_at->format('H:i') }}
            </small>
            
            @if($message->direction === 'outgoing')
                <span class="ms-1">
                    @if($message->status === 'pending')
                        <i class="bi bi-clock" title="Pending"></i>
                    @elseif($message->status === 'sent')
                        <i class="bi bi-check" title="Sent"></i>
                    @elseif($message->status === 'delivered')
                        <i class="bi bi-check-all" title="Delivered"></i>
                    @elseif($message->status === 'read')
                        <i class="bi bi-check-all text-info" title="Read"></i>
                    @elseif($message->status === 'failed')
                        <i class="bi bi-exclamation-circle text-danger" title="Failed"></i>
                    @endif
                </span>
            @endif
        </div>
    </div>
</div>
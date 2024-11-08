{{-- resources/views/components/sidebar.blade.php --}}
<div class="col-md-3 bg-dark text-white p-4">
    <h4 class="mb-3">Chat Settings</h4>
    <div class="form-group">
        <label for="personality">Choose Bot Personality:</label>
        <select id="personality" class="form-control mt-2">
            <option value="formal">Formal</option>
            <option value="friendly">Friendly</option>
            <option value="humorous">Humorous</option>
        </select>
    </div>
    <hr class="bg-white">
    <h5>Chat History</h5>
    <ul class="list-group">
        <!-- New Chat Link -->
        <a href="{{ route('chat.new') }}" class="list-group-item list-group-item-action">+ New Chat</a>
        
        <!-- Display previous chats with titles (first message) -->
        @foreach(App\Models\ChatHistory::select('session_id')
                ->distinct()
                ->get() as $session)
            
            @php
                // Retrieve the first message for the current session_id
                $firstMessage = App\Models\ChatHistory::where('session_id', $session->session_id)
                    ->orderBy('created_at', 'asc')
                    ->first();
                $title = $firstMessage ? Str::limit($firstMessage->message, 30) : 'Chat #' . ($loop->index + 1);
            @endphp
    
            <a href="{{ route('chat.index', ['sessionId' => $session->session_id]) }}" 
               class="list-group-item list-group-item-action">
                {{ $title }}
            </a>
        @endforeach
    </ul>
    
</div>

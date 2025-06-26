@php
    $histories = $this->histories;
@endphp

<style>
.timeline {
    border-left: 2px solid #e5e7eb;
    margin-left: 2rem;
    padding-left: 1rem;
}
.timeline-item {
    margin-bottom: 2rem;
    position: relative;
}
.timeline-marker {
    width: 1rem;
    height: 1rem;
    border-radius: 50%;
    position: absolute;
    left: -1.5rem;
    top: 0.2rem;
}
.timeline-content {
    margin-left: 1.5rem;
}
.badge {
    padding: 0.2em 0.6em;
    border-radius: 0.5em;
    color: #fff;
    font-size: 0.9em;
}
.bg-red-500 { background: #ef4444; }
.bg-green-500 { background: #22c55e; }
.bg-orange-500 { background: #f59e42; }
.bg-gray-400 { background: #9ca3af; }
</style>

<div class="timeline">
    @foreach($histories as $history)
        <div class="timeline-item">
            <div class="timeline-marker
                @if($history->type === 'vente') bg-red-500
                @elseif($history->type === 'achat') bg-green-500
                @elseif($history->type === 'vente (pack)') bg-orange-500
                @else bg-gray-400 @endif"></div>
            <div class="timeline-content">
                <div>
                    <strong>{{ $history->created_at->diffForHumans() }}</strong>
                    <span class="ml-2 badge
                        @if($history->type === 'vente') bg-red-500
                        @elseif($history->type === 'achat') bg-green-500
                        @elseif($history->type === 'vente (pack)') bg-orange-500
                        @else bg-gray-400 @endif">
                        {{ ucfirst($history->type) }}
                    </span>
                </div>
                <div>
                    Produit : <b>{{ $history->productUnit->product->name ?? '' }}</b>
                    ({{ $history->productUnit->unit->name ?? '' }})
                </div>
                <div>
                    Changement : <b>{{ $history->quantity_change }}</b>
                    | Avant : {{ $history->quantity_before }}
                    | Après : {{ $history->quantity_after }}
                </div>
                <div>
                    Utilisateur : {{ $history->user->name ?? 'Système' }}
                </div>
                <div>
                    <i>{{ $history->note }}</i>
                </div>
            </div>
        </div>
    @endforeach
</div>

@php
    $maxLoad = max(1, (int) $staffWorkload->max('active'));
@endphp
<section class="pipeline-workload" data-pipeline-workload-panel>
    <header>
        <div>
            <span>RECEPTION DESK</span>
            <h2>Receptionist workload</h2>
            <p>Live ownership across active enrollment conversations.</p>
        </div>
        <span class="pipeline-live-pill"><i></i> LIVE</span>
    </header>

    <div class="pipeline-workload-unassigned">
        <span><i class="fas fa-inbox"></i></span>
        <div><strong>{{ $unassignedCount }}</strong><small>Unassigned active leads</small></div>
        <a href="{{ route('admin.inquiries.index', ['assignment' => 'unassigned']) }}">Assign now <i class="fas fa-arrow-right"></i></a>
    </div>

    <div class="pipeline-workload-list">
        @forelse ($staffWorkload as $member)
            <article>
                @if ($member['avatar'])
                    <img src="{{ asset($member['avatar']) }}" alt="">
                @else
                    <span class="pipeline-workload-avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member['name'], 0, 1)) }}</span>
                @endif
                <div class="pipeline-workload-copy">
                    <div><strong>{{ $member['name'] }}</strong><small>{{ $member['active'] }} active · {{ $member['enrolled'] }} enrolled</small></div>
                    <span class="pipeline-load-track"><i style="width: {{ ($member['active'] / $maxLoad) * 100 }}%"></i></span>
                </div>
                <a href="{{ route('admin.inquiries.index', ['assignment' => $member['id']]) }}" aria-label="View {{ $member['name'] }} inquiries"><i class="fas fa-chevron-right"></i></a>
            </article>
        @empty
            <div class="pipeline-workload-empty"><i class="fas fa-user-plus"></i><span>Add a staff account to start tracking receptionist workload.</span></div>
        @endforelse
    </div>
</section>

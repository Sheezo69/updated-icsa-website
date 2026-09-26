<div class="pipeline-board" data-pipeline-board>
    @php
        $stageKeys = array_keys($stages);
    @endphp
    @foreach ($stages as $stageKey => $stage)
        @php
            $items = $pipeline[$stageKey] ?? collect();
            $stageIndex = array_search($stageKey, $stageKeys, true);
            $previousStage = $stageKeys[$stageIndex - 1] ?? null;
            $nextStage = $stageKeys[$stageIndex + 1] ?? null;
        @endphp
        <section class="pipeline-column is-{{ $stage['tone'] }}" data-pipeline-column="{{ $stageKey }}">
            <header class="pipeline-column-head">
                <span class="pipeline-column-icon"><i class="fas {{ $stage['icon'] }}"></i></span>
                <div>
                    <h2>{{ $stage['label'] }}</h2>
                    <p>{{ $stage['note'] }}</p>
                </div>
                <strong data-stage-count>{{ $items->count() }}</strong>
            </header>

            <div class="pipeline-dropzone" data-pipeline-dropzone="{{ $stageKey }}">
                @forelse ($items as $inquiry)
                    @php
                        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($inquiry->name), 0, 1));
                        $score = min(100, max(0, (int) ($inquiry->lead_score ?? 0)));
                    @endphp
                    <article class="pipeline-card" draggable="true" data-pipeline-card="{{ $inquiry->id }}" data-current-stage="{{ $stageKey }}">
                        <div class="pipeline-card-glow" aria-hidden="true"></div>
                        <header>
                            <span class="inquiry-avatar inquiry-avatar-{{ $inquiry->id % 6 }}">{{ $initial ?: '?' }}</span>
                            <div>
                                <strong>{{ $inquiry->name }}</strong>
                                <small>#{{ $inquiry->id }} · {{ optional($inquiry->created_at)->diffForHumans() }}</small>
                            </div>
                            <span class="pipeline-drag-handle" title="Drag to another stage"><i class="fas fa-grip-vertical"></i></span>
                        </header>
                        <div class="pipeline-card-course"><i class="fas fa-graduation-cap"></i><span>{{ $inquiry->course_interest ?: 'General inquiry' }}</span></div>
                        @if ($inquiry->message)
                            <p>{{ \Illuminate\Support\Str::limit($inquiry->message, 86) }}</p>
                        @endif
                        <div class="pipeline-card-signal">
                            <span><i style="--signal: {{ $score }}%"></i></span>
                            <small>{{ $score }} intent</small>
                        </div>
                        <footer>
                            <span class="pipeline-card-owner {{ $inquiry->assignedTo ? 'is-assigned' : '' }}">
                                <i class="fas {{ $inquiry->assignedTo ? 'fa-user-check' : 'fa-user-clock' }}"></i>
                                {{ $inquiry->assignedTo?->username ?? 'Unassigned' }}
                            </span>
                            <div class="pipeline-card-actions">
                                @if ($previousStage)
                                    <button type="button" data-pipeline-move="{{ $previousStage }}" title="Move back"><i class="fas fa-arrow-left"></i></button>
                                @endif
                                <a href="{{ route('admin.inquiries.index', ['open' => $inquiry->id]) }}" title="Open inquiry"><i class="far fa-eye"></i></a>
                                @if ($nextStage)
                                    <button type="button" data-pipeline-move="{{ $nextStage }}" title="Move forward"><i class="fas fa-arrow-right"></i></button>
                                @endif
                            </div>
                        </footer>
                    </article>
                @empty
                    <div class="pipeline-empty" data-pipeline-empty>
                        <i class="fas fa-circle-plus"></i>
                        <strong>Drop a card here</strong>
                        <span>This stage is ready.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>

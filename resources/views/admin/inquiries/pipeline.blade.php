@extends('admin.layout')

@section('title', 'Enrollment Pipeline')
@section('subtitle', 'Move every inquiry from first contact to confirmed enrollment.')
@section('title_icon', 'fas fa-table-columns')

@section('content')
    <div class="pipeline-page" data-pipeline-root
         data-snapshot-url="{{ route('admin.inquiries.pipeline.snapshot') }}"
         data-move-template="{{ route('admin.inquiries.pipeline.move', ['inquiry' => '__ID__']) }}"
         data-version="{{ $version }}">
        <section class="pipeline-hero">
            <div class="pipeline-hero-copy">
                <span class="pipeline-kicker"><i class="fas fa-wand-magic-sparkles"></i> LIVE ENROLLMENT FLOW</span>
                <h2>Turn every inquiry into <span>forward motion.</span></h2>
                <p>Drag cards between glowing stages. Every move saves instantly, updates the inquiry status, and appears for the whole team.</p>
            </div>
            <div class="pipeline-hero-stats">
                <div><strong>{{ $pipeline->sum->count() }}</strong><span>Visible leads</span></div>
                <div><strong>{{ $pipeline['qualified']->count() }}</strong><span>Ready now</span></div>
                <div><strong>{{ $pipeline['enrolled']->count() }}</strong><span>Enrolled</span></div>
            </div>
        </section>

        <div class="pipeline-layout">
            <main data-pipeline-board-host>
                @include('admin.inquiries.partials.pipeline-board')
            </main>
            <aside data-pipeline-workload-host>
                @include('admin.inquiries.partials.pipeline-workload')
            </aside>
        </div>

        <div class="pipeline-sync-toast" data-pipeline-toast hidden><i class="fas fa-check"></i><span></span></div>
        <p class="pipeline-screen-reader" data-pipeline-announcer aria-live="polite"></p>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-pipeline-root]');
    if (!root) return;

    const boardHost = root.querySelector('[data-pipeline-board-host]');
    const workloadHost = root.querySelector('[data-pipeline-workload-host]');
    const announcer = root.querySelector('[data-pipeline-announcer]');
    const toast = root.querySelector('[data-pipeline-toast]');
    const csrf = @json(csrf_token());
    let version = root.dataset.version;
    let draggedCard = null;
    let moving = false;
    let toastTimer = null;

    const showToast = (message, isError = false) => {
        toast.querySelector('i').className = `fas ${isError ? 'fa-triangle-exclamation' : 'fa-check'}`;
        toast.querySelector('span').textContent = message;
        toast.classList.toggle('is-error', isError);
        toast.hidden = false;
        announcer.textContent = message;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.hidden = true, 3200);
    };

    const refresh = async (force = false) => {
        if (moving || draggedCard || document.hidden) return;
        try {
            const response = await fetch(root.dataset.snapshotUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store',
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!force && data.version === version) return;
            boardHost.innerHTML = data.board_html;
            workloadHost.innerHTML = data.workload_html;
            version = data.version;
            bindBoard();
        } catch (_) {}
    };

    const moveCard = async (card, stage) => {
        if (!card || !stage || card.dataset.currentStage === stage || moving) return;
        const originalStage = card.dataset.currentStage;
        const originalZone = root.querySelector(`[data-pipeline-dropzone="${CSS.escape(originalStage)}"]`);
        const targetZone = root.querySelector(`[data-pipeline-dropzone="${CSS.escape(stage)}"]`);
        if (!targetZone) return;

        moving = true;
        card.classList.add('is-saving');
        targetZone.querySelector('[data-pipeline-empty]')?.remove();
        targetZone.append(card);
        card.dataset.currentStage = stage;
        updateCounts();

        try {
            const url = root.dataset.moveTemplate.replace('__ID__', encodeURIComponent(card.dataset.pipelineCard));
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ stage }),
            });
            if (!response.ok) throw new Error('Unable to save this move.');
            const data = await response.json();
            version = data.version;
            showToast(data.message);
            moving = false;
            card.classList.remove('is-saving');
            await refresh(true);
        } catch (error) {
            originalZone?.append(card);
            card.dataset.currentStage = originalStage;
            card.classList.remove('is-saving');
            moving = false;
            updateCounts();
            showToast(error.message || 'Unable to move this inquiry.', true);
        }
    };

    const updateCounts = () => {
        root.querySelectorAll('[data-pipeline-column]').forEach((column) => {
            const count = column.querySelectorAll('[data-pipeline-card]').length;
            column.querySelector('[data-stage-count]').textContent = count;
        });
    };

    const clearDropStates = () => root.querySelectorAll('.is-drag-target').forEach((column) => column.classList.remove('is-drag-target'));

    const bindBoard = () => {
        root.querySelectorAll('[data-pipeline-card]').forEach((card) => {
            card.addEventListener('dragstart', (event) => {
                draggedCard = card;
                card.classList.add('is-dragging');
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', card.dataset.pipelineCard);
            });
            card.addEventListener('dragend', () => {
                card.classList.remove('is-dragging');
                draggedCard = null;
                clearDropStates();
            });
        });

        root.querySelectorAll('[data-pipeline-dropzone]').forEach((zone) => {
            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                event.dataTransfer.dropEffect = 'move';
                clearDropStates();
                zone.closest('[data-pipeline-column]').classList.add('is-drag-target');
            });
            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                const card = draggedCard;
                const stage = zone.dataset.pipelineDropzone;
                draggedCard = null;
                clearDropStates();
                moveCard(card, stage);
            });
        });
    };

    root.addEventListener('click', (event) => {
        const mover = event.target.closest('[data-pipeline-move]');
        if (mover) moveCard(mover.closest('[data-pipeline-card]'), mover.dataset.pipelineMove);
    });

    bindBoard();
    window.setInterval(() => refresh(false), 8000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(false); });
})();
</script>
@endpush

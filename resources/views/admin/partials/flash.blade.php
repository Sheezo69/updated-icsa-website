@if (session('success'))
    <div class="admin-alert admin-alert-success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="admin-alert admin-alert-error">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="admin-alert admin-alert-error">
        {{ $errors->first() }}
    </div>
@endif

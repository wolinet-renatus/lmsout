@extends('layouts.admin')

@section('title', 'System Updater')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.system.status') }}">System Maintenance</a></div>
        <div class="breadcrumb-item active">System Updater</div>
    </div>
@endsection

@push('styles')
<style>
    .updater-hero {
        background: linear-gradient(135deg, #0f294a 0%, #0b4a6f 55%, #065f46 100%);
        color: #fff;
        border-radius: 10px;
        overflow: hidden;
        position: relative;
    }
    .updater-hero .stack-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #f8fafc;
        font-size: 12px;
        font-weight: 600;
        margin-right: 6px;
        margin-bottom: 6px;
    }
    .updater-version-box {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 8px;
        padding: 16px 20px;
    }
    .updater-console {
        background: #0f172a;
        color: #cbd5e1;
        border-radius: 8px;
        padding: 16px;
        max-height: 320px;
        overflow-y: auto;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12.5px;
        line-height: 1.55;
        border: 1px solid #1e293b;
    }
    .updater-console pre {
        color: #86efac;
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .migration-list {
        max-height: 180px;
        overflow-y: auto;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 14px;
    }
    .migration-list code {
        display: block;
        padding: 3px 0;
        color: #334155;
        font-size: 12px;
    }
    .deploy-step-badge {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
        flex-shrink: 0;
    }
</style>
@endpush

@section('main-content')
    <!-- Hero Version & Runtime Card -->
    <div class="card updater-hero border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-start mb-4">
                <div>
                    <div class="text-uppercase small font-weight-bold mb-2" style="letter-spacing: 1px; color: rgba(255,255,255,0.75);">
                        CI/CD Post-Deployment &amp; Version Sync
                    </div>
                    <h3 class="text-white font-weight-bold mb-2">{{ site_name() }} Platform</h3>
                    <div>
                        <span class="stack-pill"><i class="fab fa-laravel mr-1"></i> Laravel {{ $versions['laravel_version'] }}</span>
                        <span class="stack-pill"><i class="fab fa-php mr-1"></i> PHP {{ $versions['php_version'] }}</span>
                        <span class="stack-pill"><i class="fas fa-server mr-1"></i> {{ ucfirst($versions['environment']) }}</span>
                        @if ($deployment['commit'])
                            <span class="stack-pill"><i class="fas fa-code-branch mr-1"></i> {{ $deployment['branch'] ?? 'HEAD' }} &bull; {{ $deployment['commit'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="{{ route('admin.updater.index', ['recheck' => 1]) }}" class="btn btn-light btn-sm font-weight-bold shadow-sm">
                        <i class="fas fa-sync-alt mr-1"></i> Re-check After Deploy
                    </a>
                    <a href="{{ route('admin.backups.index') }}" class="btn btn-outline-light btn-sm font-weight-bold ml-1">
                        <i class="fas fa-database mr-1"></i> Backups
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="updater-version-box h-100">
                        <div class="small text-uppercase" style="color: rgba(255,255,255,0.7);">Database Version</div>
                        <div class="h2 text-white font-weight-bold mb-1 mt-1">v{{ $versions['current_version'] }}</div>
                        <div class="small" style="color: rgba(255,255,255,0.7);">Stored in platform settings</div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="updater-version-box h-100">
                        <div class="small text-uppercase" style="color: rgba(255,255,255,0.7);">Codebase Target</div>
                        <div class="d-flex align-items-center mt-1 mb-1">
                            <span class="h2 text-white font-weight-bold mb-0 mr-2">v{{ $versions['new_version'] }}</span>
                            @if ($versions['status'] === 'update_available')
                                <span class="badge badge-warning">Update Ready</span>
                            @elseif ($versions['status'] === 'mismatch')
                                <span class="badge badge-danger">Mismatch</span>
                            @else
                                <span class="badge badge-success">Aligned</span>
                            @endif
                        </div>
                        <div class="small" style="color: rgba(255,255,255,0.7);">Deployed codebase release</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="updater-version-box h-100">
                        <div class="small text-uppercase" style="color: rgba(255,255,255,0.7);">Deployment Sync State</div>
                        <div class="h5 text-white font-weight-bold mb-1 mt-2">
                            @if ($syncRequired)
                                <i class="fas fa-exclamation-circle text-warning mr-1"></i> Sync Recommended
                            @else
                                <i class="fas fa-check-circle text-success mr-1"></i> Up to Date
                            @endif
                        </div>
                        <div class="small" style="color: rgba(255,255,255,0.7);">
                            {{ $migrations['count'] > 0 ? $migrations['count'] . ' pending migration(s)' : 'All migrations applied' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Summary KPIs -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-code-branch"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Deploy Source</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        {{ $deployment['source_label'] }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-{{ $migrations['count'] > 0 ? 'warning' : 'success' }}">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Pending Migrations</h4>
                    </div>
                    <div class="card-body">
                        {{ $migrations['count'] }}
                        <span class="small text-muted font-weight-normal">/ {{ $migrations['total_count'] }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Last Codebase Deploy</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        {{ $deployment['deployed_at'] ? $deployment['deployed_at']->diffForHumans() : 'Unknown' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-history"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Last Sync Run</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        {{ $deployment['last_sync_at'] ? $deployment['last_sync_at']->diffForHumans() : 'Not recorded' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Synchronization Action & Logs -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-sync-alt text-primary mr-2"></i> Post-Deployment Synchronization</h4>
                    <span class="badge badge-{{ $syncRequired ? 'warning' : 'success' }}">
                        {{ $syncRequired ? 'Action Needed' : 'Synchronized' }}
                    </span>
                </div>
                <div class="card-body">
                    @if ($versions['status'] === 'mismatch')
                        <div class="alert alert-danger d-flex align-items-start">
                            <i class="fas fa-exclamation-triangle fa-lg mt-1 mr-3"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1">Version Mismatch Detected</h6>
                                <p class="mb-0 small">
                                    Database version (<code>{{ $versions['current_version'] }}</code>) is higher than the deployed codebase version (<code>{{ $versions['new_version'] }}</code>). Verify your Git branch or CI/CD deployment before syncing.
                                </p>
                            </div>
                        </div>
                    @elseif ($syncRequired)
                        <div class="alert alert-warning d-flex align-items-start">
                            <i class="fas fa-rocket fa-lg mt-1 mr-3"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1">Post-Deploy Update Available</h6>
                                <p class="mb-0 small">
                                    @if ($versions['status'] === 'update_available')
                                        New release <strong>v{{ $versions['new_version'] }}</strong> is ready to apply.
                                    @endif
                                    @if ($migrations['count'] > 0)
                                        Detected <strong>{{ $migrations['count'] }}</strong> pending database migration(s) from the latest deployment.
                                    @endif
                                    @if ($deployment['new_commit_detected'])
                                        New Git commit <code>{{ $deployment['commit'] }}</code> deployed since last sync (<code>{{ $deployment['last_synced_commit'] }}</code>).
                                    @endif
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-light border d-flex align-items-start">
                            <i class="fas fa-check-double text-success fa-lg mt-1 mr-3"></i>
                            <div>
                                <h6 class="font-weight-bold mb-1 text-dark">System Synchronized</h6>
                                <p class="mb-0 small text-muted">
                                    Codebase version, database schema ({{ $migrations['ran_count'] }} migrations), and runtime are up to date. You can still run a manual sync after any Git CI/CD pull to flush caches and verify links.
                                </p>
                            </div>
                        </div>
                    @endif

                    @if ($deployment['commit'] || $deployment['commit_message'])
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small font-weight-bold text-uppercase text-muted">Latest Git / CI-CD Metadata</span>
                                @if ($deployment['deployed_at'])
                                    <span class="small text-muted">{{ $deployment['deployed_at']->format('M d, Y H:i') }}</span>
                                @endif
                            </div>
                            <div class="small text-dark">
                                @if ($deployment['branch'])
                                    <span class="mr-3"><strong>Branch:</strong> <code>{{ $deployment['branch'] }}</code></span>
                                @endif
                                @if ($deployment['commit'])
                                    <span class="mr-3"><strong>Commit:</strong> <code>{{ $deployment['commit'] }}</code></span>
                                @endif
                                @if ($deployment['pipeline'])
                                    <span><strong>Pipeline:</strong> {{ $deployment['pipeline'] }}</span>
                                @endif
                            </div>
                            @if ($deployment['commit_message'])
                                <div class="small text-muted mt-1">{{ $deployment['commit_message'] }}</div>
                            @endif
                        </div>
                    @endif

                    @if ($migrations['count'] > 0)
                        <div class="mb-3">
                            <label class="font-weight-bold small text-uppercase text-muted mb-2">
                                Pending Database Migrations ({{ $migrations['count'] }})
                            </label>
                            <div class="migration-list">
                                @foreach ($migrations['pending'] as $migrationName)
                                    <code><i class="fas fa-file-code text-warning mr-1"></i> {{ $migrationName }}</code>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('admin.updater.update') }}" method="POST" id="updateForm">
                        @csrf
                        <div class="border rounded p-3 mb-4">
                            <div class="font-weight-bold small text-uppercase text-muted mb-2">Synchronization Options</div>

                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="createBackup"
                                       name="create_backup"
                                       value="1"
                                       {{ $syncRequired ? 'checked' : '' }}>
                                <label class="custom-control-label" for="createBackup">
                                    <strong>Create pre-update database backup (.sql)</strong>
                                    <span class="d-block small text-muted">
                                        Saves an automatic snapshot in <code>storage/app/backups</code> before executing migrations.
                                        @if ($backupStats['latest'])
                                            Last backup: {{ $backupStats['latest']->diffForHumans() }}.
                                        @else
                                            <span class="text-warning">No backup created yet.</span>
                                        @endif
                                    </span>
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       id="clearCache"
                                       name="clear_cache"
                                       value="1"
                                       checked>
                                <label class="custom-control-label" for="clearCache">
                                    <strong>Flush application caches after sync (<code>optimize:clear</code>)</strong>
                                    <span class="d-block small text-muted">
                                        Clears compiled config, routes, and Blade views so newly deployed changes take effect immediately.
                                    </span>
                                </label>
                            </div>
                        </div>

                        <button type="submit"
                                id="updateBtn"
                                class="btn btn-{{ $syncRequired ? 'primary' : 'outline-primary' }} btn-lg btn-block font-weight-bold">
                            <span class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true" id="spinner"></span>
                            <i class="fas fa-{{ $syncRequired ? 'rocket' : 'sync-alt' }} mr-2" id="btn-icon"></i>
                            <span id="btn-text">
                                {{ $syncRequired ? 'Run Update & Post-Deploy Sync' : 'Re-verify & Clear Deployment Caches' }}
                            </span>
                        </button>
                    </form>

                    @if (session('error'))
                        <div class="alert alert-danger mt-4 mb-0">
                            <i class="fas fa-bug mr-2"></i>
                            <strong>Update Failed:</strong> {{ session('error') }}
                        </div>
                    @endif

                    @if (session('output'))
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small font-weight-bold text-uppercase text-muted">
                                    <i class="fas fa-terminal mr-1"></i> Execution Console Log
                                </span>
                                <button type="button" class="btn btn-sm btn-light" id="copyLogBtn">
                                    <i class="fas fa-copy mr-1"></i> Copy Log
                                </button>
                            </div>
                            <div class="updater-console">
                                <pre id="updaterLogOutput">{{ session('output') }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Readiness Checklist & CI/CD Workflow -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-clipboard-check text-success mr-2"></i> Deployment Readiness Checks</h4>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-database text-muted mr-2"></i> Database Connection</span>
                            @if ($checks['database_connected'])
                                <span class="badge badge-success">Connected</span>
                            @else
                                <span class="badge badge-danger">Offline</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-code-branch text-muted mr-2"></i> Schema Migrations</span>
                            @if ($migrations['count'] === 0)
                                <span class="badge badge-success">All Applied ({{ $migrations['ran_count'] }})</span>
                            @else
                                <span class="badge badge-warning">{{ $migrations['count'] }} Pending</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-link text-muted mr-2"></i> Public Storage Symlink</span>
                            @if ($checks['storage_linked'])
                                <span class="badge badge-success">Linked</span>
                            @else
                                <span class="badge badge-warning">Missing</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-folder-open text-muted mr-2"></i> Storage Writable</span>
                            @if ($checks['storage_writable'])
                                <span class="badge badge-success">Writable</span>
                            @else
                                <span class="badge badge-danger">Not Writable</span>
                            @endif
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-bolt text-muted mr-2"></i> Config &amp; Route Cache</span>
                            <span class="badge badge-{{ ($checks['config_cached'] || $checks['routes_cached']) ? 'info' : 'light' }}">
                                {{ ($checks['config_cached'] || $checks['routes_cached']) ? 'Cached' : 'Dynamic' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-shield-alt text-muted mr-2"></i> Latest Backup Snapshot</span>
                            @if ($backupStats['latest'])
                                <a href="{{ route('admin.backups.index') }}" class="badge badge-success">
                                    {{ $backupStats['latest']->diffForHumans() }}
                                </a>
                            @else
                                <a href="{{ route('admin.backups.index') }}" class="badge badge-warning">
                                    Create Backup
                                </a>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-stream text-info mr-2"></i> Git CI/CD Deployment Workflow</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <span class="deploy-step-badge bg-primary text-white mr-3">1</span>
                        <div>
                            <div class="font-weight-bold text-dark small">Push &amp; CI/CD Deploy</div>
                            <div class="text-muted small">Pipeline pulls latest code, updates Composer packages, and compiles assets.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start mb-3">
                        <span class="deploy-step-badge bg-info text-white mr-3">2</span>
                        <div>
                            <div class="font-weight-bold text-dark small">Click "Re-check After Deploy"</div>
                            <div class="text-muted small">Verifies Git commit, pending migrations, storage symlink, and version alignment.</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-start">
                        <span class="deploy-step-badge bg-success text-white mr-3">3</span>
                        <div>
                            <div class="font-weight-bold text-dark small">Run Post-Deploy Sync</div>
                            <div class="text-muted small">Optionally creates a DB backup, executes pending migrations, updates version, and clears caches.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('updateForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('updateBtn');
        const spinner = document.getElementById('spinner');
        const icon = document.getElementById('btn-icon');
        const text = document.getElementById('btn-text');

        btn.disabled = true;
        btn.classList.add('disabled');
        spinner.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        text.innerText = 'Synchronizing System...';
    });

    document.getElementById('copyLogBtn')?.addEventListener('click', function () {
        const logText = document.getElementById('updaterLogOutput')?.innerText || '';
        if (!logText) return;

        navigator.clipboard?.writeText(logText).then(() => {
            if (window.ToastMagic) ToastMagic.success('Execution log copied to clipboard.');
        });
    });
</script>
@endpush

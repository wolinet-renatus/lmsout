@extends('layouts.admin')

@section('title', 'Backup Management')

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.settings.system.status') }}">System Maintenance</a></div>
        <div class="breadcrumb-item active">Backup Management</div>
    </div>
@endsection

@push('styles')
<style>
    .backup-option-card {
        display: block;
        border: 1.5px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: all 0.18s ease;
        background: #fff;
        position: relative;
    }
    .backup-option-card:hover:not(.is-disabled) {
        border-color: #6777ef;
        background: #f8faff;
    }
    .backup-option-card.is-selected {
        border-color: #6777ef;
        background: #f4f6ff;
        box-shadow: 0 2px 8px rgba(103, 119, 239, 0.12);
    }
    .backup-option-card.is-disabled {
        opacity: 0.6;
        cursor: not-allowed;
        background: #f8f9fa;
    }
    .backup-option-card input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .backup-option-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .backup-filter-btn.active {
        background-color: #6777ef !important;
        color: #fff !important;
        border-color: #6777ef !important;
    }
    .backup-filename {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 12.5px;
        color: #1e293b;
        word-break: break-all;
    }
</style>
@endpush

@section('main-content')
    <!-- Summary KPIs -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-primary">
                    <i class="fas fa-database"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Total Backups</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['count'] }}
                        <div class="text-muted small font-weight-normal" style="font-size: 11px; line-height: 1.2;">
                            {{ $stats['sql_count'] }} SQL &bull; {{ $stats['zip_count'] }} ZIP
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-info">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Storage Used</h4>
                    </div>
                    <div class="card-body">
                        {{ $stats['total_size'] }}
                        <div class="text-muted small font-weight-normal" style="font-size: 11px; line-height: 1.2;">
                            Free: {{ $stats['disk_free'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-success">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Latest Snapshot</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        {{ $stats['latest'] ? $stats['latest']->diffForHumans() : 'Never' }}
                        @if ($stats['latest'])
                            <div class="text-muted small font-weight-normal" style="font-size: 11px; line-height: 1.2;">
                                {{ $stats['latest']->format('M d, H:i') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
            <div class="card card-statistic-1">
                <div class="card-icon bg-{{ $stats['zip_available'] ? 'success' : 'warning' }}">
                    <i class="fas fa-file-archive"></i>
                </div>
                <div class="card-wrap">
                    <div class="card-header">
                        <h4>Archive Engine</h4>
                    </div>
                    <div class="card-body" style="font-size: 15px;">
                        {{ $stats['zip_available'] ? 'ZipArchive Ready' : 'SQL Only' }}
                        <div class="text-muted small font-weight-normal" style="font-size: 11px; line-height: 1.2;">
                            DB: {{ $stats['database_name'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Create Backup Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-plus-circle mr-2 text-primary"></i> Create New Backup</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Create an on-demand snapshot of database <code>{{ $stats['database_name'] }}</code>, uploaded media assets, or a full system archive.
                    </p>

                    <form action="{{ route('admin.backups.create') }}" method="POST" id="createBackupForm">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small text-uppercase text-muted mb-2">Select Backup Scope</label>

                            <label class="backup-option-card is-selected" for="typeDatabase">
                                <input type="radio" id="typeDatabase" name="type" value="database" checked>
                                <div class="d-flex align-items-start">
                                    <span class="backup-option-icon bg-primary text-white mr-3">
                                        <i class="fas fa-database"></i>
                                    </span>
                                    <div>
                                        <div class="font-weight-bold text-dark">Database Only (.sql)</div>
                                        <div class="small text-muted">Portable PDO dump of all tables, schema, and rows. Fast &amp; ideal before migrations.</div>
                                    </div>
                                </div>
                            </label>

                            <label class="backup-option-card {{ !$stats['zip_available'] ? 'is-disabled' : '' }}" for="typeFiles">
                                <input type="radio" id="typeFiles" name="type" value="files" {{ !$stats['zip_available'] ? 'disabled' : '' }}>
                                <div class="d-flex align-items-start">
                                    <span class="backup-option-icon bg-info text-white mr-3">
                                        <i class="fas fa-images"></i>
                                    </span>
                                    <div>
                                        <div class="font-weight-bold text-dark">Media &amp; Uploads (.zip)</div>
                                        <div class="small text-muted">Archives <code>storage/app/public</code> and <code>public/uploads</code>.</div>
                                    </div>
                                </div>
                            </label>

                            <label class="backup-option-card {{ !$stats['zip_available'] ? 'is-disabled' : '' }}" for="typeFull">
                                <input type="radio" id="typeFull" name="type" value="full" {{ !$stats['zip_available'] ? 'disabled' : '' }}>
                                <div class="d-flex align-items-start">
                                    <span class="backup-option-icon bg-success text-white mr-3">
                                        <i class="fas fa-archive"></i>
                                    </span>
                                    <div>
                                        <div class="font-weight-bold text-dark">Full System Archive (.zip)</div>
                                        <div class="small text-muted">Combines full SQL dump and all media uploads in one archive.</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block font-weight-bold" id="createBackupBtn">
                            <span class="spinner-border spinner-border-sm d-none mr-2" role="status" aria-hidden="true" id="backupSpinner"></span>
                            <i class="fas fa-cloud-download-alt mr-1" id="backupBtnIcon"></i>
                            <span id="backupBtnText">Generate Backup Now</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="card border-left-primary">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-rocket text-primary fa-lg mt-1 mr-3"></i>
                        <div>
                            <h6 class="font-weight-bold mb-1">Deploying via Git CI/CD?</h6>
                            <p class="small text-muted mb-2">
                                Create a quick Database snapshot before running pending migrations or post-deployment synchronization.
                            </p>
                            <a href="{{ route('admin.updater.index') }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-sync-alt mr-1"></i> Open System Updater
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Backup Archives List -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                    <h4 class="mb-2 mb-md-0">
                        <i class="fas fa-archive text-primary mr-2"></i> Backup Archives
                    </h4>
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="btn-group btn-group-sm mr-2 mb-1 mb-md-0" role="group" aria-label="Filter backups">
                            <button type="button" class="btn btn-outline-secondary backup-filter-btn active" data-filter="all">All</button>
                            <button type="button" class="btn btn-outline-secondary backup-filter-btn" data-filter="database">SQL</button>
                            <button type="button" class="btn btn-outline-secondary backup-filter-btn" data-filter="files">Media</button>
                            <button type="button" class="btn btn-outline-secondary backup-filter-btn" data-filter="full">Full</button>
                        </div>
                        <input type="text"
                               id="backupSearchInput"
                               class="form-control form-control-sm"
                               placeholder="Search backups..."
                               style="width: 170px;">
                    </div>
                </div>
                <div class="card-body p-0">
                    @if ($backups->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-striped mb-0" id="backupsTable">
                                <thead>
                                    <tr>
                                        <th>Archive File</th>
                                        <th>Scope</th>
                                        <th>Size</th>
                                        <th>Created</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($backups as $backup)
                                        <tr class="backup-row"
                                            data-category="{{ $backup['category'] }}"
                                            data-name="{{ strtolower($backup['name']) }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-{{ $backup['extension'] === 'zip' ? 'file-archive text-warning' : 'database text-primary' }} mr-2"></i>
                                                    <span class="backup-filename">{{ $backup['name'] }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if ($backup['category'] === 'full')
                                                    <span class="badge badge-success">Full Backup</span>
                                                @elseif ($backup['category'] === 'files')
                                                    <span class="badge badge-info">Media &amp; Uploads</span>
                                                @else
                                                    <span class="badge badge-primary">Database SQL</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap font-weight-semibold">{{ $backup['size'] }}</td>
                                            <td class="text-nowrap">
                                                <div>{{ $backup['created_at']->format('M d, Y H:i') }}</div>
                                                <div class="text-muted small">{{ $backup['created_at']->diffForHumans() }}</div>
                                            </td>
                                            <td class="text-right text-nowrap">
                                                <a href="{{ route('admin.backups.download', $backup['name']) }}"
                                                   class="btn btn-sm btn-outline-primary mr-1"
                                                   title="Download backup">
                                                    <i class="fas fa-download mr-1"></i> Download
                                                </a>
                                                <form action="{{ route('admin.backups.destroy', $backup['name']) }}"
                                                      method="POST"
                                                      class="d-inline delete-backup-form"
                                                      data-filename="{{ $backup['name'] }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger delete-backup-btn"
                                                            title="Delete backup">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="backupEmptyFilter" class="p-4 text-center text-muted d-none">
                            No backups match the current filter.
                        </div>
                    @else
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-database fa-3x mb-3 text-secondary" style="opacity: 0.4;"></i>
                            <h6 class="font-weight-bold text-dark">No Backups Found</h6>
                            <p class="mb-0 small">Select a backup scope on the left to generate your first snapshot in <code>storage/app/backups</code>.</p>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-whitesmoke d-flex justify-content-between align-items-center small text-muted">
                    <span>Storage path: <code>storage/app/backups</code></span>
                    <span>{{ $backups->count() }} archive(s)</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        // Highlight selected backup option card
        const optionCards = document.querySelectorAll('.backup-option-card');
        optionCards.forEach(card => {
            const radio = card.querySelector('input[type="radio"]');
            if (!radio) return;

            radio.addEventListener('change', () => {
                optionCards.forEach(c => c.classList.remove('is-selected'));
                if (radio.checked) {
                    card.classList.add('is-selected');
                }
            });
        });

        // Prevent double-submit during backup creation
        document.getElementById('createBackupForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('createBackupBtn');
            const spinner = document.getElementById('backupSpinner');
            const icon = document.getElementById('backupBtnIcon');
            const text = document.getElementById('backupBtnText');

            btn.disabled = true;
            btn.classList.add('disabled');
            spinner.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
            text.innerText = 'Generating Backup...';
        });

        // Filter and search backup rows
        let activeFilter = 'all';
        const rows = document.querySelectorAll('.backup-row');
        const emptyFilterNotice = document.getElementById('backupEmptyFilter');
        const searchInput = document.getElementById('backupSearchInput');

        function applyBackupFilters() {
            const query = (searchInput?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(row => {
                const category = row.dataset.category;
                const name = row.dataset.name || '';
                const matchesCategory = activeFilter === 'all' || category === activeFilter;
                const matchesSearch = query === '' || name.includes(query);

                if (matchesCategory && matchesSearch) {
                    row.classList.remove('d-none');
                    visibleCount++;
                } else {
                    row.classList.add('d-none');
                }
            });

            if (emptyFilterNotice) {
                emptyFilterNotice.classList.toggle('d-none', visibleCount > 0 || rows.length === 0);
            }
        }

        document.querySelectorAll('.backup-filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.backup-filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                activeFilter = this.dataset.filter || 'all';
                applyBackupFilters();
            });
        });

        searchInput?.addEventListener('input', applyBackupFilters);

        // SweetAlert2 delete confirmation
        document.querySelectorAll('.delete-backup-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const form = this.closest('.delete-backup-form');
                const filename = form?.dataset.filename || 'this backup';

                if (typeof Swal === 'undefined') {
                    if (confirm('Delete backup ' + filename + '?')) {
                        form.submit();
                    }
                    return;
                }

                Swal.fire({
                    title: 'Delete Backup?',
                    text: 'Are you sure you want to permanently delete "' + filename + '"?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#fc544b',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    })();
</script>
@endpush

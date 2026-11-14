@extends('layouts.admin')

@section('title', 'Invoice Installments — ' . $invoice->invoice_number)

@section('breadcrumb')
    <div class="section-header-breadcrumb">
        <div class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></div>
        <div class="breadcrumb-item"><a href="{{ route('admin.campus.installments.index') }}">Installments</a></div>
        <div class="breadcrumb-item active">{{ $invoice->invoice_number }}</div>
    </div>
@endsection

@section('main-content')
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Installment Plan: {{ $invoice->invoice_number }}</h4>
            <div>
                <a href="{{ route('admin.campus.installments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-primary"><i class="fas fa-sync mr-1"></i> Rebuild Plan</a>
                <a href="{{ route('admin.campus.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info">View Invoice</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted d-block">Student</small><strong>{{ $invoice->applicant?->full_name ?? $invoice->user?->name ?? '-' }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Course</small><strong>{{ $invoice->course?->title ?? '-' }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Paid</small><strong class="text-success">{{ currency_format((float) $invoice->paid_amount) }}</strong></div>
                <div class="col-md-3"><small class="text-muted d-block">Balance</small><strong class="text-danger">{{ currency_format((float) $invoice->balance) }}</strong></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h4>Installments</h4></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount Due</th>
                            <th>Paid</th>
                            <th>Status</th>
                            <th>Reminders Sent</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->paymentSchedules as $s)
                            <tr>
                                <td>{{ $s->installment_label ?? ('Installment #' . $s->installment_number) }}</td>
                                <td>{{ optional($s->due_date)->format('d M Y') }}</td>
                                <td>{{ currency_format((float) $s->amount_due) }}</td>
                                <td>{{ currency_format((float) $s->amount_paid) }}</td>
                                <td><span class="badge badge-{{ $s->status === 'paid' ? 'success' : ($s->status === 'overdue' ? 'danger' : ($s->status === 'cancelled' ? 'secondary' : 'warning')) }}">{{ ucfirst($s->status) }}</span></td>
                                <td>{{ $s->reminder_count }}</td>
                                <td class="text-right text-nowrap">
                                    @if (! in_array($s->status, ['paid', 'cancelled'], true))
                                        <form action="{{ route('admin.campus.installments.mark-sent', $s) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Send Reminder"><i class="fas fa-bell"></i></button>
                                        </form>
                                        <form action="{{ route('admin.campus.installments.cancel', $s) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Cancel Installment"><i class="fas fa-ban"></i></button>
                                        </form>
                                    @endif
                                    @if ((float) $s->amount_paid <= 0)
                                        <form action="{{ route('admin.campus.installments.destroy', $s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete installment?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No installment schedule entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

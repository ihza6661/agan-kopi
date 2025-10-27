@extends('layouts.app')

@section('title', 'Log Aktivitas')

@section('content')
    <section class="container-fluid py-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 class="h3 mb-0"><i class="bi bi-clipboard-data"></i> Log Aktivitas</h1>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table id="logsTable" class="table align-middle mb-0" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aktivitas</th>
                            <th>IP</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@push('css')
    <link rel="stylesheet" href="{{ asset('assets/vendor/datatables.min.css') }}">
@endpush

@push('script')
    <script src="{{ asset('assets/vendor/jquery-3.7.0.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/datatables.min.js') }}"></script>
    <script>
        (function() {
            const table = $('#logsTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('log-aktivitas.data') }}',
                    type: 'GET'
                },
                language: { url: '{{ asset('assets/vendor/id.json') }}' },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'user_name', name: 'user.name', defaultContent: '-' },
                    { data: 'activity', name: 'activity' },
                    { data: 'ip_address', name: 'ip_address', defaultContent: '-' },
                    { data: 'user_agent', name: 'user_agent', defaultContent: '-' },
                ],
                order: [[1, 'desc']],
                pageLength: 10,
            });
        })();
    </script>
@endpush

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #fff;
        }

        /* ── Header ──────────────────────────────── */
        .header {
            background: #4f46e5;
            color: #fff;
            padding: 20px 28px 16px;
            margin-bottom: 20px;
        }
        .header-top {
            width: 100%;
        }
        .header-top table {
            width: 100%;
            border-collapse: collapse;
        }
        .header h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.01em;
            margin-bottom: 2px;
        }
        .header .subtitle {
            font-size: 10px;
            opacity: 0.8;
        }
        .header .meta {
            text-align: right;
            font-size: 9px;
            opacity: 0.85;
            line-height: 1.6;
        }

        /* ── Filter Summary ──────────────────────── */
        .filter-table {
            width: calc(100% - 56px);
            margin: 0 28px 16px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .filter-table td {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 9px;
            color: #475569;
            width: 33%;
        }
        .filter-table td strong {
            color: #1e293b;
        }

        /* ── Summary row ──────────────────────────── */
        .summary-table {
            width: calc(100% - 56px);
            margin: 0 28px 16px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }
        .summary-table td {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
        }
        .summary-table .count {
            font-size: 20px;
            font-weight: 700;
            color: #4f46e5;
            line-height: 1.2;
        }
        .summary-table .label {
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ── Table ───────────────────────────────── */
        .table-wrap {
            margin: 0 28px 28px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            background: #4f46e5;
            color: #fff;
        }
        thead th {
            padding: 8px 10px;
            text-align: left;
            font-size: 8.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        tbody tr {
            border-bottom: 1px solid #f1f5f9;
        }
        tbody td {
            padding: 7px 10px;
            font-size: 9px;
            color: #334155;
            vertical-align: top;
        }
        .td-name {
            font-weight: 600;
            color: #1e293b;
        }
        .td-id {
            font-size: 8px;
            color: #94a3b8;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
            text-transform: capitalize;
        }

        /* type badge colours */
        .badge-login    { background:#dcfce7; color:#166534; }
        .badge-logout   { background:#f1f5f9; color:#475569; }
        .badge-create   { background:#e0e7ff; color:#3730a3; }
        .badge-update   { background:#dbeafe; color:#1e40af; }
        .badge-delete   { background:#fee2e2; color:#991b1b; }
        .badge-generate { background:#ccfbf1; color:#065f46; }
        .badge-export   { background:#f3e8ff; color:#6b21a8; }
        .badge-view     { background:#fff7ed; color:#9a3412; }

        /* role badge */
        .role-badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        .td-description {
            max-width: 220px;
            word-wrap: break-word;
        }
        .td-date {
            white-space: nowrap;
            color: #64748b;
        }

        /* ── Footer ──────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e2e8f0;
            padding: 6px 28px;
            font-size: 8px;
            color: #94a3b8;
        }
        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
            font-size: 11px;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <table>
                <tr>
                    <td style="vertical-align:top;">
                        <h1>Activity Logs Report</h1>
                        <div class="subtitle">PRISM &mdash; DOST</div>
                    </td>
                    <td style="text-align:right; vertical-align:top;" class="meta">
                        <div>Generated: {{ $generated }}</div>
                        <div>By: {{ $generated_by }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Filter summary -->
    <table class="filter-table">
        <tr>
            <td>Date Range: <strong>{{ $date_from }}</strong> &mdash; <strong>{{ $date_to }}</strong></td>
            <td>Action Type: <strong>{{ ucfirst($type) }}</strong></td>
            <td>Total Records: <strong>{{ count($logs) }}</strong></td>
        </tr>
    </table>

    <!-- Summary cards -->
    @php
        $typeCounts  = collect($logs)->countBy('type');
        $summaryTypes = ['login', 'create', 'update', 'delete', 'generate', 'export'];
    @endphp
    <table class="summary-table">
        <tr>
            <td>
                <div class="count">{{ count($logs) }}</div>
                <div class="label">Total Logs</div>
            </td>
            @foreach($summaryTypes as $t)
            <td>
                <div class="count">{{ $typeCounts[$t] ?? 0 }}</div>
                <div class="label">{{ ucfirst($t) }}</div>
            </td>
            @endforeach
        </tr>
    </table>

    <!-- Table -->
    <div class="table-wrap">
        @if(count($logs) > 0)
        <table>
            <thead>
                <tr>
                    <th width="4%">#</th>
                    <th width="18%">User</th>
                    <th width="12%">Role</th>
                    <th width="9%">Action</th>
                    <th width="37%">Description</th>
                    <th width="20%">Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $i => $log)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="td-name">{{ $log['name'] }}</div>
                        <div class="td-id">{{ $log['employee_id'] }}</div>
                    </td>
                    <td>
                        <span class="role-badge">{{ str_replace('_', ' ', ucwords($log['role'], '_')) }}</span>
                    </td>
                    <td>
                        <span class="badge badge-{{ $log['type'] }}">{{ $log['type'] }}</span>
                    </td>
                    <td class="td-description">{{ $log['description'] }}</td>
                    <td class="td-date">{{ $log['created_at'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data">No activity logs found for the selected filters.</div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <table>
            <tr>
                <td style="text-align:left;">DOST &mdash; PRISM</td>
                <td style="text-align:right;">Confidential &mdash; For Internal Use Only</td>
            </tr>
        </table>
    </div>

</body>
</html>

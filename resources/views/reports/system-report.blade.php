<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Report — PDRIS</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:10px; color:#1e293b; background:#fff; }

        /* ── Header ──────────────────────────────── */
        .header { background:#0047ab; padding:18px 28px 14px; margin-bottom:18px; }
        .header h1 { font-size:18px; font-weight:700; color:#fff; margin-bottom:2px; }
        .header .sub { font-size:9px; color:rgba(255,255,255,0.75); }
        .header .meta { text-align:right; font-size:8.5px; color:rgba(255,255,255,0.8); line-height:1.8; }

        /* ── Section label ───────────────────────── */
        .section-title {
            font-size:9px; font-weight:700; text-transform:uppercase;
            letter-spacing:0.07em; color:#64748b;
            margin:0 28px 8px;
            padding-bottom:4px;
            border-bottom:1px solid #e2e8f0;
        }

        /* ── Summary cards — use table for layout ─ */
        .card-table {
            width:calc(100% - 56px);
            margin:0 28px 18px;
            border-collapse:separate;
            border-spacing:8px 0;
        }
        .card-table td {
            border:1px solid #e2e8f0;
            border-radius:8px;
            padding:10px 14px;
            vertical-align:top;
        }
        .card-val { font-size:20px; font-weight:700; color:#0047ab; line-height:1.1; }
        .card-lbl { font-size:8px; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; margin-top:2px; }

        /* ── Role breakdown bar ──────────────────── */
        .role-table {
            width:calc(100% - 56px);
            margin:0 28px 18px;
            border-collapse:collapse;
        }
        .role-table td { padding:5px 0; vertical-align:middle; }
        .role-name  { font-size:9px; color:#334155; width:160px; }
        .role-bar-wrap { background:#f1f5f9; border-radius:3px; height:8px; }
        .role-bar { height:8px; border-radius:3px; }
        .role-count { font-size:9px; font-weight:700; color:#1e293b; text-align:right; width:36px; padding-left:8px; }

        /* ── Log stats ───────────────────────────── */
        .log-stat-table {
            width:calc(100% - 56px);
            margin:0 28px 18px;
            border-collapse:separate;
            border-spacing:8px 0;
        }
        .log-stat-table td {
            border:1px solid #e2e8f0;
            border-radius:8px;
            padding:8px 12px;
            text-align:center;
        }
        .log-val { font-size:15px; font-weight:700; line-height:1.2; }
        .log-lbl { font-size:7.5px; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; margin-top:2px; }

        /* ── Recent users table ──────────────────── */
        .data-table {
            width:calc(100% - 56px);
            margin:0 28px 18px;
            border-collapse:collapse;
        }
        .data-table thead tr { background:#0047ab; color:#fff; }
        .data-table thead th { padding:7px 10px; font-size:8px; font-weight:600; text-align:left; text-transform:uppercase; letter-spacing:0.04em; }
        .data-table tbody tr:nth-child(even) { background:#f8fafc; }
        .data-table tbody td { padding:6px 10px; font-size:9px; color:#334155; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
        .badge { display:inline-block; padding:2px 7px; border-radius:4px; font-size:7.5px; font-weight:600; }
        .role-super_admin          { background:#e0e7ff; color:#3730a3; }
        .role-sub_admin            { background:#f3e8ff; color:#6b21a8; }
        .role-provincial_admin     { background:#ccfbf1; color:#065f46; }
        .role-provincial_sub_admin { background:#cffafe; color:#0e7490; }
        .role-provincial_director  { background:#dbeafe; color:#1e40af; }
        .role-employee             { background:#dcfce7; color:#166534; }
        .type-login    { background:#dcfce7; color:#166534; }
        .type-create   { background:#e0e7ff; color:#3730a3; }
        .type-update   { background:#dbeafe; color:#1e40af; }
        .type-delete   { background:#fee2e2; color:#991b1b; }
        .type-generate { background:#ccfbf1; color:#065f46; }
        .type-export   { background:#f3e8ff; color:#6b21a8; }
        .type-view     { background:#fff7ed; color:#9a3412; }
        .type-logout   { background:#f1f5f9; color:#475569; }

        /* ── Footer ──────────────────────────────── */
        .footer {
            position:fixed; bottom:0; left:0; right:0;
            border-top:1px solid #e2e8f0; padding:5px 28px;
            font-size:8px; color:#94a3b8;
        }
        .footer table { width:100%; border-collapse:collapse; }

        .spacer { height:6px; }
    </style>
</head>
<body>

    <!-- ── Header ─────────────────────────────────────────────── -->
    <div class="header">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top;">
                    <h1>System Report</h1>
                    <div class="sub">Director Ranking Information System &mdash; DOST Philippines</div>
                </td>
                <td class="meta" style="vertical-align:top;">
                    <div>Period: {{ $date_from }} &mdash; {{ $date_to }}</div>
                    <div>Generated: {{ $generated }}</div>
                    <div>By: {{ $generated_by }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- ── User Account Summary ────────────────────────────────── -->
    <div class="section-title">User Account Summary</div>
    @php $us = $user_stats; @endphp
    <table class="card-table">
        <tr>
            <td><div class="card-val">{{ $us['total'] }}</div><div class="card-lbl">Total Users</div></td>
            <td><div class="card-val">{{ $us['super_admin'] }}</div><div class="card-lbl">Super Admins</div></td>
            <td><div class="card-val">{{ $us['sub_admin'] }}</div><div class="card-lbl">Sub Admins</div></td>
            <td><div class="card-val">{{ $us['provincial_admin'] }}</div><div class="card-lbl">Prov. Admins</div></td>
            <td><div class="card-val">{{ $us['provincial_sub_admin'] }}</div><div class="card-lbl">Prov. Sub Admins</div></td>
            <td><div class="card-val">{{ $us['provincial_director'] }}</div><div class="card-lbl">Directors</div></td>
            <td><div class="card-val">{{ $us['employee'] }}</div><div class="card-lbl">Employees</div></td>
        </tr>
    </table>

    <!-- ── Users by Role Distribution ─────────────────────────── -->
    <div class="section-title">Role Distribution</div>
    @php
        $roles = [
            ['label' => 'Super Admin',          'key' => 'super_admin',          'color' => '#3730a3'],
            ['label' => 'Sub Admin',             'key' => 'sub_admin',            'color' => '#6b21a8'],
            ['label' => 'Provincial Admin',      'key' => 'provincial_admin',     'color' => '#065f46'],
            ['label' => 'Provincial Sub Admin',  'key' => 'provincial_sub_admin', 'color' => '#0e7490'],
            ['label' => 'Provincial Director',   'key' => 'provincial_director',  'color' => '#1e40af'],
            ['label' => 'Employee',              'key' => 'employee',             'color' => '#166534'],
        ];
        $total = $us['total'] ?: 1;
    @endphp
    <table class="role-table">
        @foreach($roles as $role)
        @php $pct = round($us[$role['key']] / $total * 100); @endphp
        <tr>
            <td class="role-name">{{ $role['label'] }}</td>
            <td style="padding:0 12px; width:100%;">
                <div class="role-bar-wrap">
                    <div class="role-bar" style="width:{{ max($pct,1) }}%; background:{{ $role['color'] }};"></div>
                </div>
            </td>
            <td class="role-count">{{ $us[$role['key']] }}</td>
            <td style="width:36px; padding-left:4px; font-size:8px; color:#94a3b8;">{{ $pct }}%</td>
        </tr>
        @endforeach
    </table>

    <!-- ── Activity Log Summary ────────────────────────────────── -->
    <div class="section-title">Activity Log Summary ({{ $date_from }} — {{ $date_to }})</div>
    @php
        $ls = $log_stats;
        $logTotal = array_sum($ls);
        $logColors = ['login'=>'#166534','create'=>'#3730a3','update'=>'#1e40af','delete'=>'#991b1b','generate'=>'#065f46','export'=>'#6b21a8','view'=>'#9a3412','logout'=>'#475569'];
    @endphp
    <table class="log-stat-table">
        <tr>
            <td><div class="log-val" style="color:#1e293b;">{{ $logTotal }}</div><div class="log-lbl">Total Logs</div></td>
            @foreach(['login','create','update','delete','generate','export','view'] as $type)
            <td>
                <div class="log-val" style="color:{{ $logColors[$type] ?? '#1e293b' }};">{{ $ls[$type] ?? 0 }}</div>
                <div class="log-lbl">{{ ucfirst($type) }}</div>
            </td>
            @endforeach
        </tr>
    </table>

    <div class="spacer"></div>

    <!-- ── Recent Users ────────────────────────────────────────── -->
    <div class="section-title">Recently Registered Users</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="25%">Name</th>
                <th width="25%">Email</th>
                <th width="18%">Role</th>
                <th width="18%">Province</th>
                <th width="9%">Registered</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recent_users as $i => $u)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td style="font-weight:600;">{{ $u['name'] ?: '—' }}</td>
                <td>{{ $u['email'] }}</td>
                <td><span class="badge role-{{ $u['role'] }}">{{ str_replace('_',' ', ucwords($u['role'],'_')) }}</span></td>
                <td>{{ $u['province'] ?? '—' }}</td>
                <td>{{ $u['registered'] }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:16px;">No recent users found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- ── Recent Activity Logs ────────────────────────────────── -->
    <div class="section-title">Recent Activity Logs</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="18%">User</th>
                <th width="14%">Role</th>
                <th width="10%">Action</th>
                <th width="38%">Description</th>
                <th width="16%">Date & Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recent_logs as $i => $log)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <div style="font-weight:600;">{{ $log['name'] ?: '—' }}</div>
                    <div style="font-size:8px; color:#94a3b8;">{{ $log['employee_id'] }}</div>
                </td>
                <td><span class="badge role-{{ $log['role'] }}">{{ str_replace('_',' ', ucwords($log['role'],'_')) }}</span></td>
                <td><span class="badge type-{{ $log['type'] }}">{{ ucfirst($log['type']) }}</span></td>
                <td style="word-wrap:break-word;">{{ $log['description'] }}</td>
                <td>{{ $log['created_at'] }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#94a3b8; padding:16px;">No activity logs found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- ── Footer ─────────────────────────────────────────────── -->
    <div class="footer">
        <table>
            <tr>
                <td>DOST &mdash; Director Ranking Information System</td>
                <td style="text-align:right;">Confidential &mdash; For Internal Use Only</td>
            </tr>
        </table>
    </div>

</body>
</html>

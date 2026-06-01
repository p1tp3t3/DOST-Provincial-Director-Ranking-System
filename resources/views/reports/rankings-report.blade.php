<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Provincial Director Rankings Report {{ $year }}</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DejaVu Sans',Arial,sans-serif; font-size:10px; color:#1e293b; background:#fff; }

        /* Header */
        .header { background:#0047ab; padding:18px 28px 14px; margin-bottom:18px; }
        .header table { width:100%; border-collapse:collapse; }
        .header td { vertical-align:top; }
        .header h1 { font-size:17px; font-weight:700; color:#fff; margin-bottom:2px; }
        .header .sub { font-size:9px; color:rgba(255,255,255,0.75); }
        .header .meta { text-align:right; font-size:8.5px; color:rgba(255,255,255,0.8); line-height:1.7; }

        /* Filter bar */
        .filter-table { width:calc(100% - 56px); margin:0 28px 14px; border-collapse:separate; border-spacing:6px 0; }
        .filter-table td { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:6px; padding:5px 10px; font-size:9px; color:#475569; }
        .filter-table td strong { color:#1e293b; }

        /* Summary cards */
        .summary-table { width:calc(100% - 56px); margin:0 28px 16px; border-collapse:separate; border-spacing:8px 0; }
        .summary-table td { border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; text-align:center; }
        .summary-table .val { font-size:18px; font-weight:700; color:#0047ab; line-height:1.2; }
        .summary-table .lbl { font-size:8px; color:#64748b; text-transform:uppercase; letter-spacing:0.05em; }

        /* Table */
        .table-wrap { margin:0 28px 24px; }
        table.rankings { width:100%; border-collapse:collapse; }
        table.rankings thead tr { background:#0047ab; color:#fff; }
        table.rankings thead th { padding:8px 10px; font-size:8.5px; font-weight:600; text-align:left; text-transform:uppercase; letter-spacing:0.04em; }
        table.rankings tbody tr:nth-child(even) { background:#f8fafc; }
        table.rankings tbody tr { border-bottom:1px solid #f1f5f9; }
        table.rankings tbody td { padding:7px 10px; font-size:9px; color:#334155; vertical-align:middle; }
        .rank-cell { text-align:center; font-weight:700; }
        .score-bar-wrap { display:inline-block; width:80px; background:#e2e8f0; border-radius:3px; height:6px; vertical-align:middle; }
        .score-bar { height:6px; border-radius:3px; display:inline-block; }
        .score-text { font-weight:700; font-size:9px; }
        .cat-badge { display:inline-block; padding:2px 8px; border-radius:4px; font-size:8px; font-weight:600; }
        .cat-micro  { background:#e0e7ff; color:#3730a3; }
        .cat-small  { background:#ccfbf1; color:#065f46; }
        .cat-medium { background:#dbeafe; color:#1e40af; }
        .cat-large  { background:#f3e8ff; color:#6b21a8; }
        .medal { font-size:11px; }

        /* Footer */
        .footer { position:fixed; bottom:0; left:0; right:0; border-top:1px solid #e2e8f0; padding:5px 28px; font-size:8px; color:#94a3b8; }
        .footer table { width:100%; border-collapse:collapse; }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>Provincial Director Rankings Report</h1>
                    <div class="sub">Director Ranking Information System &mdash; DOST Philippines</div>
                </td>
                <td class="meta">
                    <div>Year: {{ $year }}</div>
                    <div>Category: {{ $category }}</div>
                    <div>Generated: {{ $generated }}</div>
                    <div>By: {{ $generated_by }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Filter summary -->
    @php
        $avg  = count($scores) ? round(collect($scores)->avg('score'), 1) : 0;
        $high = count($scores) ? $scores[0]['score'] : 0;
        $low  = count($scores) ? $scores[count($scores)-1]['score'] : 0;
    @endphp
    <table class="filter-table">
        <tr>
            <td>Year: <strong>{{ $year }}</strong></td>
            <td>Category Filter: <strong>{{ $category }}</strong></td>
            <td>Total Provinces Ranked: <strong>{{ count($scores) }}</strong></td>
        </tr>
    </table>

    <!-- Summary cards -->
    <table class="summary-table">
        <tr>
            <td><div class="val">{{ count($scores) }}</div><div class="lbl">Provinces</div></td>
            <td><div class="val">{{ $avg }}%</div><div class="lbl">Average Score</div></td>
            <td><div class="val">{{ $high }}%</div><div class="lbl">Highest Score</div></td>
            <td><div class="val">{{ $low }}%</div><div class="lbl">Lowest Score</div></td>
        </tr>
    </table>

    <!-- Rankings table -->
    <div class="table-wrap">
        @if(count($scores) > 0)
        <table class="rankings">
            <thead>
                <tr>
                    <th width="5%" style="text-align:center;">Rank</th>
                    <th width="22%">Province</th>
                    <th width="22%">Director</th>
                    <th width="10%" style="text-align:center;">Category</th>
                    <th width="8%"  style="text-align:center;">Indicators</th>
                    <th width="33%">Score</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scores as $i => $row)
                @php
                    $barWidth = min($row['score'], 100);
                    $barColor = $row['score'] >= 90 ? '#22c55e' : ($row['score'] >= 75 ? '#3b82f6' : ($row['score'] >= 60 ? '#f59e0b' : '#ef4444'));
                    $catClass = 'cat-' . ($row['category'] ?? 'micro');
                    $medal    = $i === 0 ? '🥇' : ($i === 1 ? '🥈' : ($i === 2 ? '🥉' : ($i + 1)));
                @endphp
                <tr>
                    <td class="rank-cell">{{ $medal }}</td>
                    <td style="font-weight:600;">{{ $row['province'] }}</td>
                    <td>{{ $row['director'] }}</td>
                    <td style="text-align:center;">
                        <span class="cat-badge {{ $catClass }}">{{ ucfirst($row['category'] ?? '') }}</span>
                    </td>
                    <td style="text-align:center;">{{ $row['count'] }}</td>
                    <td>
                        <table style="border-collapse:collapse; width:100%;">
                            <tr>
                                <td style="width:100px; padding-right:8px;">
                                    <div class="score-bar-wrap">
                                        <div class="score-bar" style="width:{{ $barWidth }}%; background:{{ $barColor }};"></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="score-text" style="color:{{ $barColor }};">{{ $row['score'] }}%</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div style="text-align:center; padding:40px; color:#94a3b8;">No ranking data found for the selected filters.</div>
        @endif
    </div>

    <!-- Footer -->
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

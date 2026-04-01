<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Exam List - Print</title>
    <style>
        :root { --blue: #003471; --blue2: #004a9f; --border: #cfe0ff; --text: #0f172a; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: var(--text); background: #fff; }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 14mm 12mm;
            background: #fff;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-left: 6px solid var(--blue);
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(0,52,113,0.06) 0%, rgba(0,74,159,0.03) 100%);
        }
        .brand { display: flex; gap: 10px; align-items: center; min-width: 0; }
        .logo {
            width: 44px; height: 44px; border-radius: 50%;
            border: 2px solid var(--blue);
            background: #fff;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            flex: 0 0 auto;
        }
        .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .logo-fallback {
            width: 100%; height: 100%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: #fff; background: var(--blue);
            letter-spacing: 1px;
        }
        .school { min-width: 0; }
        .school .name { font-size: 16px; font-weight: 800; color: var(--blue); line-height: 1.2; }
        .school .meta { font-size: 12px; color: #334155; line-height: 1.3; }

        .title-block { text-align: right; }
        .title { font-size: 18px; font-weight: 900; color: var(--blue); margin: 0; }
        .subtitle { font-size: 12px; color: #334155; margin-top: 2px; }

        .filters {
            margin-top: 10px;
            padding: 8px 10px;
            border: 1px dashed var(--border);
            border-radius: 10px;
            color: #334155;
            font-size: 12px;
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .filters b { color: var(--blue); }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        thead th {
            background: linear-gradient(135deg, var(--blue) 0%, var(--blue2) 100%);
            color: #fff;
            font-size: 12px;
            text-align: left;
            padding: 8px 8px;
            border: 1px solid var(--blue);
        }
        tbody td {
            font-size: 12px;
            padding: 7px 8px;
            border: 1px solid var(--border);
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: rgba(0,52,113,0.03); }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            border: 1px solid var(--border);
            background: #f0f6ff;
            color: var(--blue);
            font-weight: 700;
            white-space: nowrap;
        }

        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 2px solid var(--blue);
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            color: #334155;
        }

        .no-print { display: none; }

        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff; }
            .page { width: auto; min-height: auto; margin: 0; padding: 12mm 10mm; }
        }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="brand">
            <div class="logo">
                @php
                    $logoPath = $settings->logo ?? null;
                    $hasLogo = $logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath);
                    $schoolName = $settings->school_name ?? $settings->system_name ?? 'School';
                    $fallback = strtoupper(substr(preg_replace('/\s+/', '', $schoolName), 0, 3));
                @endphp
                @if($hasLogo)
                    <img src="{{ asset('storage/' . $logoPath) }}" alt="Logo">
                @else
                    <div class="logo-fallback">{{ $fallback }}</div>
                @endif
            </div>
            <div class="school">
                <div class="name">{{ $schoolName }}</div>
                <div class="meta">
                    @if(!empty($settings->address)) {{ $settings->address }} @endif
                    @if(!empty($settings->phone))
                        @if(!empty($settings->address)) • @endif
                        {{ $settings->phone }}
                    @endif
                </div>
            </div>
        </div>
        <div class="title-block">
            <h1 class="title">Exam List</h1>
            <div class="subtitle">Printed at: {{ \Carbon\Carbon::parse($printedAt)->format('d-m-Y h:i A') }}</div>
        </div>
    </div>

    <div class="filters">
        <div><b>Campus:</b> {{ $filterCampus ? $filterCampus : 'All' }}</div>
        @if(request('search'))
            <div><b>Search:</b> {{ request('search') }}</div>
        @endif
        <div><b>Total:</b> {{ isset($exams) ? $exams->count() : 0 }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width: 42px;">#</th>
            <th style="width: 120px;">Campus</th>
            <th>Exam Name</th>
            <th>Description</th>
            <th style="width: 92px;">Exam Date</th>
            <th style="width: 95px;">Session</th>
            <th style="width: 120px;">Result Status</th>
        </tr>
        </thead>
        <tbody>
        @forelse($exams as $i => $exam)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><span class="badge">{{ $exam->campus }}</span></td>
                <td><b style="color:#003471;">{{ $exam->exam_name }}</b></td>
                <td>{{ $exam->description ? $exam->description : 'N/A' }}</td>
                <td>
                    @if($exam->exam_date)
                        {{ \Carbon\Carbon::parse($exam->exam_date)->format('d M Y') }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $exam->session }}</td>
                <td>
                    @if($exam->result_status ?? false)
                        <span class="badge">Result Declared</span>
                    @else
                        <span class="badge" style="background:#fff7ed;border-color:#fed7aa;color:#9a3412;">Not Declared</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center; padding: 16px; color:#64748b;">
                    No exams found.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>Generated by: {{ auth()->user()->name ?? (Auth::guard('admin')->user()->name ?? (Auth::guard('accountant')->user()->name ?? 'System')) }}</div>
        <div>Page 1</div>
    </div>
</div>

@if($autoPrint)
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 300);
        });
    </script>
@endif
</body>
</html>


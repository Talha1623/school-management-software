<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birthday Card - {{ $staff->name }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; background: #f3f1ff; color: #111; }
        .card {
            width: 820px;
            height: 520px;
            margin: 20px auto;
            background: radial-gradient(circle at 20% 20%, #ffecd1 0%, rgba(255,236,209,0) 45%),
                        radial-gradient(circle at 80% 30%, #ffd6e7 0%, rgba(255,214,231,0) 45%),
                        radial-gradient(circle at 40% 80%, #d6f0ff 0%, rgba(214,240,255,0) 45%),
                        #7b2cff;
            border-radius: 18px;
            border: 2px solid #ffffff;
            box-shadow: 0 12px 30px rgba(0,0,0,0.18);
            position: relative;
            overflow: hidden;
            color: #fff;
        }
        .confetti {
            position: absolute;
            inset: 0;
            background-image:
                radial-gradient(circle, rgba(255,255,255,0.6) 2px, transparent 2px),
                radial-gradient(circle, rgba(255,255,255,0.5) 1.5px, transparent 1.5px),
                radial-gradient(circle, rgba(255,255,255,0.4) 1px, transparent 1px);
            background-size: 120px 120px, 80px 80px, 60px 60px;
            opacity: 0.25;
        }
        .header {
            text-align: center;
            padding: 24px 20px 10px;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 1px;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .body {
            position: relative;
            z-index: 1;
            padding: 10px 40px 0;
            text-align: center;
        }
        .photo-wrap {
            width: 140px;
            height: 140px;
            margin: 14px auto 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 4px solid rgba(255,255,255,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
        }
        .name {
            font-size: 26px;
            font-weight: 800;
            margin-top: 6px;
        }
        .subtitle {
            font-size: 16px;
            font-weight: 600;
            margin-top: 6px;
            opacity: 0.95;
        }
        .details {
            margin-top: 10px;
            font-size: 14px;
            opacity: 0.95;
        }
        .footer {
            position: absolute;
            bottom: 16px;
            width: 100%;
            text-align: center;
            font-size: 13px;
            opacity: 0.9;
        }
        .print-btn {
            margin-top: 14px;
            padding: 8px 16px;
            border: 1px solid #ffffff;
            background: rgba(0,0,0,0.25);
            color: #fff;
            border-radius: 8px;
            cursor: pointer;
        }
        @media print {
            body { background: #fff; }
            .print-btn { display: none; }
            .card { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="confetti"></div>
        <div class="header">🎉 Happy Birthday! 🎉</div>
        <div class="body">
            <div class="photo-wrap">
                @if($staff->photo)
                    <img src="{{ Storage::url($staff->photo) }}" alt="{{ $staff->name }}" class="photo">
                @else
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: rgba(255,255,255,0.3); display: flex; align-items: center; justify-content: center; font-size: 48px;">👤</div>
                @endif
            </div>
            <div class="name">{{ $staff->name }}</div>
            @if($staff->designation)
                <div class="subtitle">{{ $staff->designation }}</div>
            @endif
            @if($staff->campus)
                <div class="details">{{ $staff->campus }}</div>
            @endif
            @if($staff->birthday)
                <div class="details" style="margin-top: 8px;">
                    {{ \Carbon\Carbon::parse($staff->birthday)->format('d M Y') }}
                </div>
            @endif
        </div>
        <div class="footer">
            <div>Printed on: {{ $printedAt }}</div>
            @if(!request()->get('auto_print'))
                <button class="print-btn" onclick="window.print()">Print Card</button>
            @endif
        </div>
    </div>
    @if(request()->get('auto_print'))
        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    @endif
</body>
</html>

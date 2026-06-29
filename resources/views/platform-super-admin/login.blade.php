<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Platform Super Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
        .container { max-width: 420px; margin: 80px auto; background: #fff; border-radius: 8px; padding: 28px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        h1 { margin: 0 0 8px; font-size: 24px; color: #1f2937; }
        p { margin: 0 0 20px; color: #6b7280; }
        label { display: block; margin-bottom: 6px; font-size: 13px; color: #374151; }
        input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 14px; box-sizing: border-box; }
        button { width: 100%; padding: 11px; border: 0; border-radius: 6px; background: #1d4ed8; color: #fff; cursor: pointer; }
        .error { color: #b91c1c; font-size: 12px; margin-top: -8px; margin-bottom: 10px; }
        .alert { background: #fef2f2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 14px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Platform Login</h1>
        <p>Sign in as platform super admin.</p>

        @if(session('error'))
            <div class="alert">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('platform-admin.login.post') }}">
            @csrf
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @if(isset($errors) && $errors->has('email'))
                <div class="error">{{ $errors->first('email') }}</div>
            @endif

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            @if(isset($errors) && $errors->has('password'))
                <div class="error">{{ $errors->first('password') }}</div>
            @endif

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

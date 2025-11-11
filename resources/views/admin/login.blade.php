<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    
    <title>Super Admin Login - ICMS</title>
    
    <style>
        body {
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #003471;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .login-header p {
            color: #6c757d;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #003471;
            font-weight: 500;
            font-size: 14px;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #003471;
            box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.1);
        }
        .input-group {
            position: relative;
        }
        .input-group-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 18px;
        }
        .input-group .form-control {
            padding-left: 45px;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 52, 113, 0.3);
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .remember-me input {
            margin-right: 8px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Super Admin Login</h2>
            <p>Enter your credentials to access the admin panel</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login') }}">
            @csrf
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="ri-mail-line input-group-icon"></i>
                    <input 
                        type="email" 
                        class="form-control @error('email') is-invalid @enderror" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="Enter your email" 
                        required 
                        autofocus
                    >
                </div>
                @error('email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <i class="ri-lock-line input-group-icon"></i>
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>
                @error('password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember" style="color: #6c757d; font-size: 14px; cursor: pointer;">Remember me</label>
            </div>

            <button type="submit" class="btn-login">
                <i class="ri-login-box-line" style="margin-right: 8px;"></i>
                Login
            </button>
        </form>
    </div>
</body>
</html>


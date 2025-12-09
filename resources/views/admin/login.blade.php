<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/remixicon.css') }}">
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    
    <title>Admin Login - ICMS</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #003471 0%, #004a9f 50%, #0066cc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background Elements - Small Bubbles */
        body::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            bottom: -60px;
            left: 10%;
            animation: bubbleUp 15s infinite ease-in-out;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 50%;
            bottom: -40px;
            right: 15%;
            animation: bubbleUp 18s infinite ease-in-out 2s;
        }
        
        /* Additional Small Bubbles */
        .bubble {
            position: absolute;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: bubbleUp 12s infinite ease-in-out;
        }
        
        .bubble:nth-child(1) {
            width: 60px;
            height: 60px;
            left: 5%;
            bottom: -30px;
            animation-delay: 0s;
        }
        
        .bubble:nth-child(2) {
            width: 100px;
            height: 100px;
            left: 25%;
            bottom: -50px;
            animation-delay: 3s;
        }
        
        .bubble:nth-child(3) {
            width: 70px;
            height: 70px;
            right: 8%;
            bottom: -35px;
            animation-delay: 1.5s;
        }
        
        .bubble:nth-child(4) {
            width: 90px;
            height: 90px;
            right: 30%;
            bottom: -45px;
            animation-delay: 4s;
        }
        
        .bubble:nth-child(5) {
            width: 50px;
            height: 50px;
            left: 50%;
            bottom: -25px;
            animation-delay: 2.5s;
        }
        
        @keyframes bubbleUp {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0.3;
            }
            50% {
                transform: translateY(-100vh) scale(1.2);
                opacity: 0.1;
            }
            100% {
                transform: translateY(-100vh) scale(0.8);
                opacity: 0;
            }
        }
        
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(255, 255, 255, 0.1);
            padding: 50px 40px;
            backdrop-filter: blur(10px);
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .logo-container {
            margin-bottom: 20px;
        }
        
        .logo-container img {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 52, 113, 0.2);
        }
        
        .login-header h1 {
            color: #003471;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: #6c757d;
            font-size: 15px;
            font-weight: 400;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #003471;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 20px;
            z-index: 2;
            transition: color 0.3s ease;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px 14px 52px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8f9fa;
            color: #212529;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #003471;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(0, 52, 113, 0.1);
        }
        
        .form-control:focus + .input-icon {
            color: #003471;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }
        
        .form-control.is-invalid + .input-icon {
            color: #dc3545;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .error-message::before {
            content: '⚠';
            font-size: 14px;
        }
        
        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #003471;
        }
        
        .remember-me label {
            color: #495057;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(0, 52, 113, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 52, 113, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #f0f9ff;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .alert-icon {
            font-size: 20px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 30px;
            color: #6c757d;
            font-size: 13px;
        }
        
        .footer-text a {
            color: #003471;
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-text a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .login-container {
                padding: 40px 30px;
                border-radius: 20px;
            }
            
            .login-header h1 {
                font-size: 28px;
            }
            
            .logo-container img {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Bubbles -->
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <img src="{{ asset('assets/images/logo-icon.png') }}" alt="ICMS Logo" onerror="this.style.display='none'">
                </div>
                <h1>Admin Login</h1>
                <p>Welcome back! Please login to your account</p>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line alert-icon"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    <i class="ri-checkbox-circle-line alert-icon"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="ri-mail-line input-icon"></i>
                        <input 
                            type="email" 
                            class="form-control {{ isset($errors) && $errors->has('email') ? 'is-invalid' : '' }}" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            placeholder="Enter your email address" 
                            required 
                            autofocus
                        >
                    </div>
                    @if(isset($errors) && $errors->has('email'))
                        <div class="error-message">{{ $errors->first('email') }}</div>
                    @endif
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="ri-lock-line input-icon"></i>
                        <input 
                            type="password" 
                            class="form-control {{ isset($errors) && $errors->has('password') ? 'is-invalid' : '' }}" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required
                        >
                    </div>
                    @if(isset($errors) && $errors->has('password'))
                        <div class="error-message">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="ri-login-box-line"></i>
                    <span>Sign In</span>
                </button>
            </form>
            
            <div class="footer-text">
                <p>&copy; {{ date('Y') }} ICMS. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh CSRF token to prevent "Page Expired" error
        document.addEventListener('DOMContentLoaded', function() {
            function refreshCSRFToken() {
                // Use route helper to get correct URL (without /api prefix)
                const loginUrl = '{{ route("admin.login") }}';
                
                fetch(loginUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                        'Cache-Control': 'no-cache'
                    },
                    cache: 'no-store'
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newToken = doc.querySelector('meta[name="csrf-token"]');
                    
                    if (newToken) {
                        // Update CSRF token in meta tag
                        const metaToken = document.querySelector('meta[name="csrf-token"]');
                        if (metaToken) {
                            metaToken.setAttribute('content', newToken.getAttribute('content'));
                        }
                        
                        // Update CSRF token in form
                        const formToken = document.querySelector('input[name="_token"]');
                        if (formToken) {
                            formToken.value = newToken.getAttribute('content');
                        }
                    }
                })
                .catch(error => {
                    console.log('CSRF token refresh failed:', error);
                });
            }

            // Refresh token when user focuses on the page (comes back to tab)
            window.addEventListener('focus', refreshCSRFToken);
            
            // Refresh token every 30 minutes to keep it fresh
            setInterval(refreshCSRFToken, 1800000); // 30 minutes

            // Refresh token before form submission if page is older than 30 minutes
            const form = document.querySelector('form[method="POST"]');
            if (form) {
                const pageLoadTime = Date.now();
                
                form.addEventListener('submit', function(e) {
                    const pageAge = Date.now() - pageLoadTime;
                    
                    // If page is older than 30 minutes, refresh token first
                    if (pageAge > 1800000) {
                        e.preventDefault();
                        
                        refreshCSRFToken();
                        
                        // Wait a bit for token to refresh, then submit
                        setTimeout(function() {
                            form.submit();
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>
</html>

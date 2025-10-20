<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #075E54 0%, #128C7E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }
        .login-body {
            padding: 3rem 2rem;
        }
        .google-btn {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 0.5rem;
            padding: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }
        .google-btn:hover {
            border-color: #4285f4;
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
        }
        .whatsapp-icon {
            font-size: 4rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card mx-auto">
            <div class="login-header">
                <i class="bi bi-whatsapp whatsapp-icon"></i>
                <h2 class="mt-3 mb-2">WhatsApp Blast App</h2>
                <p class="mb-0 opacity-75">Automate your WhatsApp messaging with AI</p>
            </div>
            
            <div class="login-body">
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <h5 class="text-center mb-4">Sign in to continue</h5>
                
                <a href="{{ route('auth.google') }}" class="google-btn w-100 text-decoration-none text-dark">
                    <svg width="24" height="24" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>

                <div class="mt-4 text-center text-muted small">
                    <p class="mb-0">By signing in, you agree to our</p>
                    <p class="mb-0">Terms of Service and Privacy Policy</p>
                </div>

                <hr class="my-4">

                <div class="text-center">
                    <h6 class="mb-3">Features</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-success">
                                <i class="bi bi-check-circle fs-4"></i>
                                <p class="small mb-0 mt-2">Mass Blasting</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="bi bi-robot fs-4"></i>
                                <p class="small mb-0 mt-2">AI Agent</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="bi bi-calendar-check fs-4"></i>
                                <p class="small mb-0 mt-2">Scheduling</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-success">
                                <i class="bi bi-graph-up fs-4"></i>
                                <p class="small mb-0 mt-2">Analytics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
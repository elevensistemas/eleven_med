@extends('layouts.app')

@section('content')
<div class="login-wrapper">
    <div class="login-background" style="background-image: url('{{ asset('images/login-bg.png') }}');"></div>
    <div class="login-overlay"></div>
    
    <div class="container d-flex align-items-center justify-content-center min-vh-100 position-relative z-1">
        <div class="glass-login-card">
            <div class="login-header text-center mb-4">
                <div class="brand-logo mb-3">
                    <div class="eye-icon-wrapper">
                        <i class="bi bi-eye"></i> <!-- Assumes bootstrap icons -->
                    </div>
                </div>
                <h3 class="fw-bold gradient-text">SISTEMA CORTALEZZI</h3>
                <p class="text-muted small">Gestión Clínica y Oftalmología</p>
            </div>

            <form method="POST" action="{{ route('login') }}" class="modern-form">
                @csrf

                <div class="form-floating mb-4">
                    <input id="email" type="email" class="form-control custom-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="name@example.com">
                    <label for="email"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="form-floating mb-4">
                    <input id="password" type="password" class="form-control custom-input @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Contraseña">
                    <label for="password"><i class="bi bi-lock me-2"></i>Contraseña</label>
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check custom-checkbox">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-muted small" for="remember">
                            Mantener sesión
                        </label>
                    </div>
                    @if (Route::has('password.request'))
                        <a class="text-decoration-none small gradient-text fw-medium" href="{{ route('password.request') }}">
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn btn-login w-100 py-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2">
                    Ingresar al Sistema <i class="bi bi-arrow-right"></i>
                </button>
            </form>
            
            <div class="text-center mt-5 login-footer text-muted small opacity-75">
                <p class="mb-0">&copy; {{ date('Y') }} Cortalezzi v2.0 - Core Engine</p>
            </div>
        </div>
    </div>
</div>
@endsection

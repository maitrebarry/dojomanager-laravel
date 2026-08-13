@php
    $currentLocale = app()->getLocale();
    $currentDirection = config("app.supported_locales.$currentLocale.dir", 'ltr');
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}" dir="{{ $currentDirection }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.auth.login_title') }} - {{ __('messages.app_name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --login-primary: #152645;
            --login-accent: #4060a0;
            --login-danger: #dc2626;
            --login-bg: #e8efff;
            --login-text: #1f2937;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background-color: var(--login-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--login-text);
            overflow: hidden;
        }

        [dir="rtl"] body {
            text-align: right;
        }

        .split-layout {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .image-side {
            flex: 1;
            position: relative;
            background: url('{{ asset('images/dojaManager.png') }}') center top/cover no-repeat var(--login-primary);
        }

        .login-side {
            flex: 0 0 520px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--login-bg);
            padding: 24px;
            position: relative;
            z-index: 10;
        }

        /* The SVG wave divider */
        .wave-divider {
            position: absolute;
            top: 0;
            right: -1px; /* Align perfectly with the login side */
            height: 100%;
            width: 150px;
            z-index: 5;
        }

        [dir="rtl"] .wave-divider {
            right: auto;
            left: -1px;
            transform: scaleX(-1);
        }

        .wave-divider svg {
            height: 100%;
            width: 100%;
            display: block;
        }

        /* EXACT ORIGINAL LOGIN CARD STYLES */
        .login-container {
            width: 100%;
            max-width: 520px;
        }

        .login-card {
            border: 0;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.26);
            overflow: hidden;
            width: 100%;
        }

        .login-header {
            background: var(--login-primary);
            color: #fff;
            padding: 30px 34px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.22);
            margin-bottom: 14px;
            font-size: 22px;
        }

        .login-header h1 {
            font-size: 28px;
            margin: 0;
            font-weight: 700;
            letter-spacing: 0;
        }

        .login-header p {
            margin: 8px 0 0;
            opacity: 0.78;
        }

        .login-body {
            background: #fff;
            padding: 34px;
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 7px;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #d7dee9;
            padding: 12px 14px;
            min-height: 46px;
        }

        .form-control:focus {
            border-color: var(--login-accent);
            box-shadow: 0 0 0 0.2rem rgba(64, 96, 160, 0.16);
        }

        .password-field {
            position: relative;
        }

        .password-field .form-control {
            padding-right: 48px;
        }

        [dir="rtl"] .password-field .form-control {
            padding-right: 14px;
            padding-left: 48px;
        }

        .password-field .form-control.is-invalid {
            background-position: right 48px center;
            padding-right: 78px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        [dir="rtl"] .toggle-password {
            right: auto;
            left: 12px;
        }

        .login-language {
            position: absolute;
            top: 18px;
            right: 18px;
            z-index: 20;
        }

        [dir="rtl"] .login-language {
            right: auto;
            left: 18px;
        }

        .toggle-password:hover,
        .toggle-password:focus {
            color: var(--login-primary);
            background: rgba(64, 96, 160, 0.08);
            outline: none;
        }

        .invalid-feedback {
            display: block;
        }

        .btn-login {
            background: var(--login-primary);
            border-color: var(--login-primary);
            border-radius: 8px;
            min-height: 46px;
            font-weight: 700;
            width: 100%;
            color: #fff;
        }

        .btn-login:hover,
        .btn-login:focus {
            background: #0f1c34;
            border-color: #0f1c34;
            color: #fff;
        }

        .login-link {
            color: var(--login-accent);
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 8px;
        }

        @media (max-width: 992px) {
            .split-layout {
                flex-direction: column;
            }
            .image-side {
                display: none;
            }
            .login-side {
                flex: 1;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Image Side (Left) -->
        <div class="image-side">
            <!-- Wavy oblique SVG divider -->
            <div class="wave-divider">
                <svg viewBox="0 0 100 1000" preserveAspectRatio="none">
                    <path d="M100,0 L0,0 C40,250 -40,500 60,750 C110,875 0,1000 0,1000 L100,1000 Z" fill="#e8efff" />
                </svg>
            </div>
        </div>

        <!-- Login Side (Right) -->
        <div class="login-side">
            <div class="dropdown login-language">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" type="button">
                    <i class="fas fa-language"></i> {{ strtoupper(app()->getLocale()) }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @foreach(config('app.supported_locales') as $locale => $localeConfig)
                        <li>
                            <form method="POST" action="{{ route('language.update', $locale) }}">
                                @csrf
                                <button type="submit" class="dropdown-item {{ app()->getLocale() === $locale ? 'active' : '' }}">
                                    {{ $localeConfig['native'] }}
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
            <main class="login-container">
                <div class="card login-card">
                    <div class="login-header">
                        <div class="brand-mark">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h1>{{ __('messages.app_name') }}</h1>
                        <p>{{ __('messages.auth.login_subtitle') }}</p>
                    </div>

                    <div class="login-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <strong>{{ __('messages.auth.login_failed_title') }}</strong>
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.cancel') }}"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-times-circle"></i> {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.cancel') }}"></button>
                            </div>
                        @endif

                        @if(session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.cancel') }}"></button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="phone" class="form-label">{{ __('messages.auth.phone') }}</label>
                                <input type="text"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       id="phone"
                                       name="phone"
                                       value="{{ old('phone') }}"
                                       placeholder="67205736"
                                       autofocus>
                                @error('phone')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">{{ __('messages.auth.password') }}</label>
                                <div class="password-field">
                                    <input type="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           id="password"
                                           name="password"
                                           placeholder="{{ __('messages.auth.password_placeholder') }}">
                                    <button type="button"
                                            class="toggle-password"
                                            aria-label="{{ __('messages.auth.show_password') }}"
                                            aria-pressed="false"
                                            data-password-toggle="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check mb-0">
                                    <input class="form-check-input"
                                           type="checkbox"
                                           name="remember"
                                           id="remember"
                                           {{ old('remember') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="remember">
                                        {{ __('messages.auth.remember_me') }}
                                    </label>
                                </div>
                                <a class="login-link" href="{{ route('password.request') }}">{{ __('messages.auth.forgot_password') }}</a>
                            </div>

                            <button type="submit" class="btn btn-login">
                                <i class="fas fa-right-to-bracket"></i> {{ __('messages.auth.login') }}
                            </button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const passwordInput = document.getElementById(button.dataset.passwordToggle);
                const icon = button.querySelector('i');
                const isHidden = passwordInput.type === 'password';

                passwordInput.type = isHidden ? 'text' : 'password';
                button.setAttribute('aria-label', isHidden ? @json(__('messages.auth.hide_password')) : @json(__('messages.auth.show_password')));
                button.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                icon.classList.toggle('fa-eye', !isHidden);
                icon.classList.toggle('fa-eye-slash', isHidden);
            });
        });
    </script>
</body>
</html>

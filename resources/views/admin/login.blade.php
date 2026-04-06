@php
    $siteSettings = \App\Models\SiteSetting::current();
    $siteName = $siteSettings->resolvedSiteName();
    $siteBrowserIconUrl = asset($siteSettings->resolvedBrandIconAssetPath());
@endphp
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin Login' }} - {{ $siteName }}</title>
    <link rel="icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="shortcut icon" href="{{ $siteBrowserIconUrl }}">
    <link rel="stylesheet" href="{{ asset('admin.css') }}">
    <style>
        body {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.96), transparent 28%),
                radial-gradient(circle at bottom right, rgba(126, 146, 255, 0.08), transparent 32%),
                linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
        }
        .admin-login-shell {
            width: min(100%, 26rem);
            display: grid;
            gap: 1rem;
        }
        .admin-login-panel {
            border: 1px solid rgba(27, 36, 48, 0.06);
            border-radius: 1.75rem;
            padding: 2.25rem 2.35rem 2rem;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 1.5rem 3rem rgba(19, 30, 49, 0.08);
        }
        .admin-login-panel h1 {
            margin: 0;
            font-size: 1.95rem;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }
        .admin-login-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            color: #758092;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.01em;
        }
        .admin-login-brand-mark {
            width: 1.4rem;
            height: 1.4rem;
            border-radius: 0.45rem;
            object-fit: cover;
            box-shadow: 0 0.4rem 1rem rgba(95, 114, 255, 0.18);
        }
        .admin-login-head {
            display: grid;
            gap: 0.9rem;
            margin-top: 0.65rem;
            margin-bottom: 2rem;
        }
        .admin-login-divider {
            width: 2.6rem;
            height: 0.24rem;
            border-radius: 999rem;
            background: linear-gradient(90deg, #5f72ff 0%, #7c89ff 100%);
            box-shadow: 0 0.45rem 1rem rgba(95, 114, 255, 0.18);
        }
        .admin-login-form {
            display: grid;
            gap: 1.2rem;
        }
        .admin-login-field {
            display: grid;
            gap: 0.65rem;
        }
        .admin-login-field label {
            font-size: 0.92rem;
            font-weight: 700;
            color: #334055;
        }
        .admin-login-field input {
            width: 100%;
            min-height: 3rem;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(214, 221, 232, 0.95);
            border-radius: 0.95rem;
            background: #fff;
            color: #172033;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
        }
        .admin-login-field input:focus {
            outline: 0;
            border-color: rgba(95, 114, 255, 0.55);
            box-shadow: 0 0 0 0.28rem rgba(95, 114, 255, 0.12);
        }
        .admin-login-alert {
            margin-bottom: 1.1rem;
            padding: 0.9rem 1rem;
            border: 1px solid rgba(226, 102, 102, 0.18);
            border-radius: 1rem;
            background: rgba(226, 102, 102, 0.08);
            color: #b44141;
            font-size: 0.92rem;
        }
        .admin-login-button {
            width: 100%;
            min-height: 3.25rem;
            border: 0;
            border-radius: 0.95rem;
            background: linear-gradient(180deg, #1d2640 0%, #131a2d 100%);
            color: #fff;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 0.85rem 1.8rem rgba(19, 26, 45, 0.18);
            transition: transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
        }
        .admin-login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 1rem 2.15rem rgba(19, 26, 45, 0.22);
        }
        .admin-login-button:active {
            transform: translateY(1px);
        }
        .admin-login-forgot {
            display: inline-flex;
            justify-self: start;
            align-items: center;
            gap: 0.35rem;
            margin-top: -0.1rem;
            color: #5f72ff;
            font-size: 0.92rem;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.18s ease, opacity 0.18s ease;
        }
        .admin-login-forgot:hover {
            color: #4458e6;
        }
        @media (max-width: 520px) {
            body {
                padding: 1.2rem;
            }
            .admin-login-panel {
                padding: 1.75rem 1.35rem 1.5rem;
                border-radius: 1.4rem;
            }
            .admin-login-panel h1 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>
    <main class="admin-login-shell">
        <section class="admin-login-panel">
            <div class="admin-login-brand">
                <img src="{{ $siteBrowserIconUrl }}" alt="" class="admin-login-brand-mark">
                <span>{{ $siteName }}</span>
            </div>
            <div class="admin-login-head">
                <h1>&#31649;&#29702;&#21592;&#30331;&#24405;</h1>
                <span class="admin-login-divider" aria-hidden="true"></span>
            </div>

            @if ($errors->any())
                <div class="admin-login-alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="admin-login-form">
                @csrf
                <div class="admin-login-field">
                    <label for="login">&#36134;&#21495;</label>
                    <input id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autofocus>
                </div>
                <div class="admin-login-field">
                    <label for="password">&#23494;&#30721;</label>
                    <input id="password" name="password" type="password" autocomplete="current-password">
                </div>
                <a
                    href="{{ asset('admin-reset-password-guide.txt') }}"
                    download="后台管理员密码重置说明.txt"
                    class="admin-login-forgot"
                >
                    &#24536;&#35760;&#23494;&#30721;
                </a>
                <button type="submit" class="admin-login-button">&#30331;&#24405;</button>
            </form>
        </section>
    </main>
</body>
</html>

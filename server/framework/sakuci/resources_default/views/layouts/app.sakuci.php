<!doctype html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sakuci Framework')</title>

    {{-- Fonts & Bootstrap 5.3 & Icons CDN --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-body-font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            --bs-body-bg: #0b0f17;
            --bs-body-color: #e2e8f0;
            --bs-tertiary-bg: #111724;
            --bs-border-color: #1e293b;
        }
        body { font-family: var(--bs-body-font-family); background-color: #0b0f17; }
        code, pre, .font-monospace { font-family: 'JetBrains Mono', monospace !important; }
        .glass-nav {
            background: rgba(15, 23, 42, 0.88) !important;
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(51, 65, 85, 0.6) !important;
        }
        .hero-glow {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 600px;
            height: 380px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.18) 0%, rgba(99, 102, 241, 0.08) 50%, transparent 70%);
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
        }
        .card-custom {
            background: #111726;
            border: 1px solid #1e293b;
            border-radius: 14px;
            transition: all 0.25s ease;
        }
        .card-custom:hover {
            border-color: #38bdf8;
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -5px rgba(14, 165, 233, 0.2);
        }
        .btn-gradient-sky {
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            border: none;
            color: #ffffff;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-gradient-sky:hover {
            background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
            color: #ffffff;
            box-shadow: 0 0 20px rgba(14, 165, 233, 0.45);
            transform: translateY(-1px);
        }
        .code-box {
            background: #070a0f;
            border: 1px solid #1e293b;
            border-radius: 10px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 position-relative">

<div class="hero-glow"></div>

@include('partials.navbar')

<main class="container flex-grow-1 py-4 position-relative" style="z-index: 1;">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="py-3 border-top border-secondary-subtle text-center text-body-secondary small mt-auto position-relative" style="z-index: 1; border-color: #1e293b !important;">
    <div class="container d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
        <span>⚡ <strong>Sakuci Framework</strong> &copy; {{ date('Y') }} &bull; Lightweight MVC Engine</span>
        <span class="badge bg-dark-subtle border border-secondary-subtle text-body-secondary font-monospace">PHP 8.2+ &bull; MySQL Active</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')

</body>
</html>

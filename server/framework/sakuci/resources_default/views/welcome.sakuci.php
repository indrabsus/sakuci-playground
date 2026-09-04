@extends('layouts.app')

@section('title', 'Sakuci Framework - Kerangka MVC Modern')

@section('content')
<div class="row justify-content-center pt-2 pb-5">
    <div class="col-lg-10 text-center">
        <!-- Tagline Badge -->
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill mb-4 border shadow-sm" style="background: rgba(14, 165, 233, 0.1); border-color: rgba(56, 189, 248, 0.3) !important;">
            <span class="badge rounded-pill bg-sky-500 text-dark fw-bold text-xs">v1.0.0</span>
            <span class="text-sky-300 small fw-medium">Kerangka MVC Ringan Modern Tanpa Composer</span>
        </div>

        <!-- Main Headline -->
        <h1 class="display-4 fw-extrabold mb-3 text-white tracking-tight" style="font-weight: 800; letter-spacing: -0.03em;">
            Kerangka PHP Rasa Laravel, <br>
            <span style="background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Murni Tanpa Perlu Composer</span>
        </h1>

        <!-- Subtitle -->
        <p class="lead text-secondary mb-4 mx-auto" style="max-width: 720px; font-size: 1.08rem; line-height: 1.6;">
            Didesain khusus untuk belajar &amp; membangun aplikasi web berbasis <strong>Model-View-Controller (MVC)</strong> secara instan di browser. Dilengkapi dengan <strong>Routing Ekspresif</strong>, <strong>Blade Templating</strong>, <strong>Active Record ORM</strong>, dan <strong>Terminal CLI</strong> interaktif.
        </p>

        <!-- CTA Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
            <a href="/mahasiswa" class="btn btn-gradient-sky btn-lg px-4 py-2.5 shadow-lg d-flex align-items-center gap-2">
                <span>🎓 Uji CRUD Mahasiswa</span>
                <i class="bi bi-arrow-right"></i>
            </a>
            <a href="/docs" class="btn btn-outline-secondary text-light btn-lg px-4 py-2.5 border-secondary d-flex align-items-center gap-2" style="background: rgba(30, 41, 59, 0.5);">
                <span>📖 Dokumentasi Framework (/docs)</span>
            </a>
        </div>

        <div class="text-secondary small font-monospace">
            <span>💡 Tip: Buka tab <strong class="text-sky-400">Terminal</strong> dan jalankan perintah <code class="text-sky-300 bg-dark px-1.5 py-0.5 rounded border border-secondary">php sakuci route:list</code></span>
        </div>
    </div>
</div>

<!-- 3 Feature Cards Grid -->
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card card-custom h-100 p-3">
            <div class="card-body">
                <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8; font-size: 1.4rem;">
                    🎯
                </div>
                <h5 class="card-title fw-bold text-white mb-2">Blade Templating Engine</h5>
                <p class="card-text text-secondary small mb-3">
                    Gunakan hierarki layout induk <code>@extends('layouts.app')</code>, <code>@section('content')</code>, dan <code>@yield</code> layaknya template engine modern.
                </p>
                <div class="code-box p-2.5 font-monospace text-xs text-sky-300">
                    <div>@extends('layouts.app')</div>
                    <div class="text-secondary ps-2">@section('content')</div>
                    <div class="text-white ps-3">&lt;h1&gt;Halo Sakuci&lt;/h1&gt;</div>
                    <div class="text-secondary ps-2">@endsection</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom h-100 p-3">
            <div class="card-body">
                <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(99, 102, 241, 0.15); color: #818cf8; font-size: 1.4rem;">
                    📦
                </div>
                <h5 class="card-title fw-bold text-white mb-2">Active Record Model ORM</h5>
                <p class="card-text text-secondary small mb-3">
                    Kelola data database relasional secara elegan tanpa query raw SQL rumit. Terhubung langsung ke database latihan MySQL.
                </p>
                <div class="code-box p-2.5 font-monospace text-xs text-indigo-300">
                    <div class="text-secondary">// Ambil &amp; manipulasi data</div>
                    <div>$mhs = Mahasiswa::all();</div>
                    <div class="text-secondary">Mahasiswa::create([...]);</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-custom h-100 p-3">
            <div class="card-body">
                <div class="d-inline-flex p-2.5 rounded-3 mb-3" style="background: rgba(16, 185, 129, 0.15); color: #34d399; font-size: 1.4rem;">
                    💻
                </div>
                <h5 class="card-title fw-bold text-white mb-2">Interactive Terminal CLI</h5>
                <p class="card-text text-secondary small mb-3">
                    Manfaatkan perintah CLI mini-artisan langsung di browser untuk otomasi pembuatan model, controller, migrasi, dan cek rute.
                </p>
                <div class="code-box p-2.5 font-monospace text-xs text-emerald-300">
                    <div>sakuci@cli:~$ php sakuci</div>
                    <div class="text-secondary">make:model Produk</div>
                    <div class="text-secondary">route:list</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Architecture Summary Card -->
<div class="card card-custom p-4 border-secondary-subtle">
    <div class="row align-items-center g-4">
        <div class="col-lg-7">
            <h5 class="fw-bold text-white mb-2 d-flex align-items-center gap-2">
                <span>📁</span> Struktur Direktori Bersih &amp; Terorganisir
            </h5>
            <p class="text-secondary small mb-3">
                Playground ini menyusun berkas aplikasi Anda persis seperti struktur project web produksi:
            </p>
            <div class="row g-2 text-xs font-monospace">
                <div class="col-sm-6"><span class="text-sky-400">app/Controllers/</span> - Logika pengontrol aksi</div>
                <div class="col-sm-6"><span class="text-sky-400">app/Models/</span> - Entitas model ORM database</div>
                <div class="col-sm-6"><span class="text-sky-400">routes/web.php</span> - Peta rute URL aplikasi</div>
                <div class="col-sm-6"><span class="text-sky-400">resources/views/</span> - Berkas template antarmuka</div>
            </div>
        </div>
        <div class="col-lg-5 text-lg-end">
            <a href="/mahasiswa" class="btn btn-outline-primary px-3 py-2 text-xs font-monospace d-inline-flex align-items-center gap-1.5">
                <span>Buka Data Mahasiswa</span>
                <i class="bi bi-box-arrow-up-right"></i>
            </a>
        </div>
    </div>
</div>
@endsection

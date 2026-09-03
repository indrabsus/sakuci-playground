@extends('layouts.app')

@section('title', config('app.name') . ' -- Kerangka PHP Ringan')

@section('content')

    {{-- Hero --}}
    <section class="text-center py-4 py-lg-5">
        <span class="badge rounded-pill badge-brand px-3 py-2 mb-3">Sakuci v1.0.0</span>

        <h1 class="display-5 fw-bold mb-3">
            Kerangka PHP rasa Laravel,<br class="d-none d-md-inline">
            <span class="text-brand">tanpa Composer</span>
        </h1>

        <p class="lead text-secondary mx-auto mb-4" style="max-width: 620px;">
            Route, Model, View, dan Controller dalam satu paket ringan.
            Cukup PHP OOP murni -- salin foldernya, jalankan, selesai.
        </p>

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a class="btn btn-brand btn-lg px-4" href="#langkah">Mulai dari sini</a>
            <a class="btn btn-outline-brand btn-lg px-4" href="https://github.com/indrabsus/sakuci-framework" target="_blank">GitHub</a>
        </div>

        <p class="text-secondary small mt-3 mb-0">
            Panduan langkah demi langkah ada di berkas
            <code class="inline">TUTORIAL.md</code>
        </p>
    </section>

    {{-- Instalasi --}}
    <section class="row g-4 align-items-start mb-5">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">📥 Instalasi</h2>

                    <p class="text-secondary mb-3">Pilih salah satu cara untuk memulai:</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <h3 class="h6 fw-medium mb-2">Dari GitHub</h3>
                            <pre class="code"><span class="cmt"># Clone repository</span>
git clone https://github.com/indrabsus/sakuci-framework.git
cd sakuci-framework

<span class="cmt"># Siapkan .env</span>
cp .env.example .env

<span class="cmt"># Jalankan server</span>
php sakuci serve</pre>
                        </div>

                        <div class="col-md-6">
                            <h3 class="h6 fw-medium mb-2">Manual</h3>
                            <ol class="mb-0 ps-4">
                                <li>Download folder dari <a href="https://github.com/indrabsus/sakuci-framework" target="_blank">GitHub</a></li>
                                <li>Ekstrak ke folder proyek</li>
                                <li>Salin <code class="inline">.env.example</code> jadi <code class="inline">.env</code></li>
                                <li>Jalankan <code class="inline">php sakuci serve</code></li>
                                <li>Buka <code class="inline">http://127.0.0.1:8000</code></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Langkah berikutnya --}}
    <section id="langkah" class="row g-4 align-items-start mb-5">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Langkah berikutnya</h2>

                    <ul class="list-unstyled d-grid gap-3 mb-0">
                        <li class="d-flex gap-3">
                            <span class="step-number">1</span>
                            <div>
                                <div class="fw-medium">Atur koneksi database</div>
                                <div class="text-secondary small">
                                    Edit berkas <code class="inline">.env</code>, lalu uji dengan
                                    <code class="inline">php sakuci db:check</code>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="step-number">2</span>
                            <div>
                                <div class="fw-medium">Buat tabel</div>
                                <div class="text-secondary small">
                                    <code class="inline">php sakuci make:migration create_buku_table</code>
                                    lalu <code class="inline">php sakuci migrate</code>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="step-number">3</span>
                            <div>
                                <div class="fw-medium">Buat model, controller, dan view</div>
                                <div class="text-secondary small">
                                    <code class="inline">make:model</code>,
                                    <code class="inline">make:controller</code>,
                                    <code class="inline">make:view</code>
                                </div>
                            </div>
                        </li>
                        <li class="d-flex gap-3">
                            <span class="step-number">4</span>
                            <div>
                                <div class="fw-medium">Daftarkan route</div>
                                <div class="text-secondary small">
                                    Tulis di <code class="inline">routes/web.php</code>, cek dengan
                                    <code class="inline">php sakuci route:list</code>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Perintah yang sering dipakai</h2>

                    <pre class="code"><span class="cmt"># jalankan server</span>
php sakuci serve

<span class="cmt"># uji koneksi database</span>
php sakuci db:check

<span class="cmt"># lihat semua route</span>
php sakuci route:list

<span class="cmt"># bantuan lengkap</span>
php sakuci</pre>
                </div>
            </div>
        </div>
    </section>

    {{-- Empat pilar --}}
    <section>
        <h2 class="h5 fw-semibold mb-3">Empat pilar</h2>

        <div class="row row-cols-1 row-cols-md-2 g-4">
            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-body border-0 pt-3 pb-0">
                        <span class="fw-semibold">Route</span>
                        <span class="text-secondary small ms-1">routes/web.php</span>
                    </div>
                    <div class="card-body">
                        <pre class="code">Route::get('/buku',
    [BukuController::class, 'index'])
    ->name('buku.index');

<span class="cmt">// 7 route CRUD sekaligus</span>
Route::resource('buku',
    BukuController::class);</pre>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-body border-0 pt-3 pb-0">
                        <span class="fw-semibold">Controller</span>
                        <span class="text-secondary small ms-1">app/Controllers</span>
                    </div>
                    <div class="card-body">
                        <pre class="code">class BukuController extends Controller
{
    public function index()
    {
        return view('buku.index', [
            'buku' => Buku::all(),
        ]);
    }
}</pre>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-body border-0 pt-3 pb-0">
                        <span class="fw-semibold">Model</span>
                        <span class="text-secondary small ms-1">app/Models</span>
                    </div>
                    <div class="card-body">
                        <pre class="code">Buku::all();
Buku::find(1);
Buku::where('stok', '>', 0)
    ->latest()
    ->paginate(10);

Buku::create(['judul' => 'PHP']);</pre>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-body border-0 pt-3 pb-0">
                        <span class="fw-semibold">View</span>
                        <span class="text-secondary small ms-1">resources/views</span>
                    </div>
                    <div class="card-body">
                        <pre class="code">@@extends('layouts.app')

@@section('content')
    @@foreach ($buku as $b)
        &lt;h2&gt;&#123;&#123; $b-&gt;judul &#125;&#125;&lt;/h2&gt;
    @@endforeach
@@endsection</pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection

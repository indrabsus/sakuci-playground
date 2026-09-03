@extends('layouts.app')

@section('title', 'Dokumentasi Sakuci Framework')

@section('content')

    <div class="row g-4">
        {{-- Sidebar navigation (desktop only) --}}
        <div class="col-lg-3 d-none d-lg-block">
            <div class="list-group sticky-top border-0" style="top: 90px;">
                @foreach ($sections as $section)
                    <a class="list-group-item list-group-item-action border-0 px-2 py-2 small"
                       href="#{{ $section['id'] }}">
                        {{ $section['title'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Main content --}}
        <div class="col-lg-9">
            <div class="mb-5">
                <span class="badge rounded-pill badge-brand px-3 py-2 mb-3">Dokumentasi</span>
                <h1 class="h2 mb-2">Sakuci Framework</h1>
                <p class="text-secondary">Panduan lengkap: CLI, Routing, Model, Controller, View, Bootstrap UI, Validation, Database, Session, Middleware, dan Multi-Role Login.</p>
            </div>

            {{-- Content sections --}}
            @foreach ($sections as $section)
                <section id="{{ $section['id'] }}" class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">{{ $section['title'] }}</h2>

                        @foreach ($section['parts'] as $part)
                            {{-- Sub-heading --}}
                            @isset ($part['h3'])
                                <h3 class="h6 text-uppercase text-brand fw-bold mt-4 mb-3 pb-2 border-bottom">
                                    {{ $part['h3'] }}
                                </h3>
                            @endisset

                            {{-- Paragraph --}}
                            @isset ($part['p'])
                                <p class="mb-3">
                                    {!! $part['p'] !!}
                                </p>
                            @endisset

                            {{-- Note/Warning --}}
                            @isset ($part['note'])
                                <div class="alert alert-info small mb-3">
                                    <strong>💡 Catatan:</strong> {{ $part['note'] }}
                                </div>
                            @endisset

                            {{-- Code block --}}
                            @isset ($part['code'])
                                <div class="mb-3">
                                    <pre class="code"><code>{{ $part['code'] }}</code></pre>
                                </div>
                            @endisset

                            {{-- Live preview: render actual HTML output above its code --}}
                            @isset ($part['preview'])
                                <div class="demo-preview mb-3">
                                    <div class="demo-preview-label">Tampilan</div>
                                    <div class="demo-preview-body">
                                        {!! $part['preview'] !!}
                                    </div>
                                </div>
                            @endisset

                            {{-- Table --}}
                            @isset ($part['table'])
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                @foreach (array_keys($part['table'][0] ?? []) as $header)
                                                    <th>{{ ucfirst($header) }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($part['table'] as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <td>
                                                            @if (str_starts_with($cell, '/'))
                                                                <code class="inline">{{ $cell }}</code>
                                                            @else
                                                                {{ $cell }}
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endisset
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- Footer --}}
            <div class="text-center text-secondary small mt-5 pt-4 border-top">
                <p>Untuk pertanyaan atau kontribusi, kunjungi <a href="https://github.com/indrabsus/sakuci-framework" target="_blank">GitHub repository</a>.</p>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <script>
        // Highlight current section in sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        document.querySelectorAll('.list-group-item').forEach(link => {
                            link.classList.remove('active');
                            if (link.getAttribute('href') === '#' + id) {
                                link.classList.add('active');
                                link.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        });
                    }
                });
            }, { rootMargin: '-30% 0px -70% 0px' });

            document.querySelectorAll('section[id]').forEach(section => {
                observer.observe(section);
            });
        });
    </script>
@endsection


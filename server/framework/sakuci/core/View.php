<?php

namespace Sakuci;

/**
 * Template engine ringan bergaya Blade.
 *
 * File view disimpan di resources/views dengan akhiran .sakuci.php lalu
 * dikompilasi menjadi PHP biasa di storage/framework/views (dicache
 * berdasarkan waktu modifikasi file sumber).
 *
 * Sintaks yang didukung:
 *   {{ $var }}  {!! $html !!}  {{-- komentar --}}
 *   @extends @section @endsection @show @yield @include @parent
 *   @if @elseif @else @endif @unless @isset @empty @error
 *   @foreach @endforeach @forelse @empty @endforelse @for @while
 *   @php @endphp @csrf @method @json @dd
 */
class View
{
    /** Section yang sudah selesai dirender. */
    protected static array $sections = [];

    /** Tumpukan nama section yang sedang dibuka. */
    protected static array $sectionStack = [];

    /** Layout yang di-extend oleh view saat ini. */
    protected static ?string $layout = null;

    /** Data yang tersedia di semua view. */
    protected static array $shared = [];

    /*
    |----------------------------------------------------------------------
    | Render
    |----------------------------------------------------------------------
    */

    public static function share(string|array $key, mixed $value = null): void
    {
        foreach (is_array($key) ? $key : [$key => $value] as $k => $v) {
            static::$shared[$k] = $v;
        }
    }

    public static function make(string $view, array $data = []): string
    {
        $data = array_merge(static::$shared, $data);

        static::$sections     = [];
        static::$sectionStack = [];
        static::$layout       = null;

        $content = static::evaluate(static::compiled($view), $data);

        // Selama view masih meng-@extends layout, render layout-nya.
        // Perulangan ini membuat layout bertingkat ikut berfungsi.
        while (static::$layout !== null) {
            $layout         = static::$layout;
            static::$layout = null;
            $content        = static::evaluate(static::compiled($layout), $data);
        }

        static::$sections     = [];
        static::$sectionStack = [];

        return $content;
    }

    public static function exists(string $view): bool
    {
        return is_file(static::path($view));
    }

    public static function path(string $view): string
    {
        $view = str_replace(['.', '\\'], '/', $view);

        return resource_path('views/' . $view . '.sakuci.php');
    }

    /*
    |----------------------------------------------------------------------
    | Kompilasi
    |----------------------------------------------------------------------
    */

    /** Kembalikan path file PHP hasil kompilasi (buat ulang bila sumber berubah). */
    protected static function compiled(string $view): string
    {
        $source = static::path($view);

        if (! is_file($source)) {
            throw new \RuntimeException("View [{$view}] tidak ditemukan di {$source}");
        }

        $cacheDir = storage_path('framework/views');

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $compiled = $cacheDir . '/' . md5($source) . '.php';

        $expired = ! is_file($compiled)
            || filemtime($compiled) < filemtime($source)
            || config('app.debug', false) && config('view.always_recompile', false);

        if ($expired) {
            file_put_contents($compiled, static::compileString(file_get_contents($source) ?: ''));
        }

        return $compiled;
    }

    public static function compileString(string $template): string
    {
        $template = static::compileComments($template);
        $template = static::compileRawPhp($template);
        $template = static::compileEchos($template);
        $template = static::compileDirectives($template);

        return $template;
    }

    protected static function compileComments(string $value): string
    {
        return (string) preg_replace('/\{\{--.*?--\}\}/s', '', $value);
    }

    protected static function compileRawPhp(string $value): string
    {
        return (string) preg_replace('/@php(.*?)@endphp/s', '<?php$1?>', $value);
    }

    protected static function compileEchos(string $value): string
    {
        // {!! ... !!} -> tanpa escape
        $value = (string) preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?= $1 ?>', $value);

        // {{ ... }} -> di-escape
        return (string) preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?= e($1) ?>', $value);
    }

    /**
     * Kompilasi seluruh direktif @nama(...) â€” tanda kurung dicocokkan secara
     * rekursif sehingga ekspresi seperti @if(count($a) > 0) tetap utuh.
     */
    protected static function compileDirectives(string $value): string
    {
        $pattern = '/\B@(@?\w+)([ \t]*)(\((?:[^()]++|(?3))*\))?/';

        $forelseDepth = 0;

        return (string) preg_replace_callback($pattern, function (array $match) use (&$forelseDepth): string {
            $name  = $match[1];
            $raw   = $match[3] ?? '';
            $expr  = $raw !== '' ? substr($raw, 1, -1) : '';
            $space = $match[2] ?? '';

            // @@if menjadi teks literal "@if".
            if (str_starts_with($name, '@')) {
                return substr($match[0], 1);
            }

            switch ($name) {
                case 'extends':
                    return "<?php \\Sakuci\\View::extend({$expr}); ?>";

                case 'section':
                    return "<?php \\Sakuci\\View::startSection({$expr}); ?>";

                case 'endsection':
                case 'stop':
                    return '<?php \\Sakuci\\View::stopSection(); ?>';

                case 'show':
                    return '<?= \\Sakuci\\View::yieldContent(\\Sakuci\\View::stopSection()) ?>';

                case 'parent':
                    return '<?= \\Sakuci\\View::parentContent() ?>';

                case 'yield':
                    return "<?= \\Sakuci\\View::yieldContent({$expr}) ?>";

                case 'include':
                    // get_defined_vars() diletakkan di depan agar argumen view
                    // dan data tambahan tetap bisa ditulis seperti Laravel.
                    return "<?= \\Sakuci\\View::insert(get_defined_vars(), {$expr}) ?>";

                case 'includeIf':
                    return "<?= \\Sakuci\\View::insertIf(get_defined_vars(), {$expr}) ?>";

                case 'if':
                    return "<?php if({$expr}): ?>";

                case 'elseif':
                    return "<?php elseif({$expr}): ?>";

                case 'else':
                    return '<?php else: ?>';

                case 'endif':
                    return '<?php endif; ?>';

                case 'unless':
                    return "<?php if(! ({$expr})): ?>";

                case 'endunless':
                    return '<?php endif; ?>';

                case 'isset':
                    return "<?php if(isset({$expr})): ?>";

                case 'endisset':
                    return '<?php endif; ?>';

                case 'foreach':
                    return "<?php foreach({$expr}): ?>";

                case 'endforeach':
                    return '<?php endforeach; ?>';

                case 'forelse':
                    $forelseDepth++;

                    return "<?php \$__empty_{$forelseDepth} = true; foreach({$expr}): \$__empty_{$forelseDepth} = false; ?>";

                case 'empty':
                    // Tanpa argumen berarti bagian "kosong" dari @forelse.
                    if ($expr === '' && $forelseDepth > 0) {
                        return "<?php endforeach; if(\$__empty_{$forelseDepth}): ?>";
                    }

                    return "<?php if(empty({$expr})): ?>";

                case 'endempty':
                    return '<?php endif; ?>';

                case 'endforelse':
                    $forelseDepth = max(0, $forelseDepth - 1);

                    return '<?php endif; ?>';

                case 'for':
                    return "<?php for({$expr}): ?>";

                case 'endfor':
                    return '<?php endfor; ?>';

                case 'while':
                    return "<?php while({$expr}): ?>";

                case 'endwhile':
                    return '<?php endwhile; ?>';

                case 'continue':
                    return $expr === '' ? '<?php continue; ?>' : "<?php if({$expr}) continue; ?>";

                case 'break':
                    return $expr === '' ? '<?php break; ?>' : "<?php if({$expr}) break; ?>";

                case 'error':
                    return "<?php if(\$message = errors()->first({$expr})): ?>";

                case 'enderror':
                    return '<?php endif; unset($message); ?>';

                case 'csrf':
                    return '<?= \\Sakuci\\View::csrfField() ?>';

                case 'method':
                    return "<?= \\Sakuci\\View::methodField({$expr}) ?>";

                case 'json':
                    return "<?= json_encode({$expr}, JSON_UNESCAPED_UNICODE) ?>";

                case 'dd':
                    return "<?php dd({$expr}); ?>";

                case 'dump':
                    return "<?php dump({$expr}); ?>";

                default:
                    // Bukan direktif yang dikenal (mis. alamat email) -> biarkan apa adanya.
                    return $match[0];
            }
        }, $value);
    }

    /*
    |----------------------------------------------------------------------
    | Runtime yang dipanggil oleh hasil kompilasi
    |----------------------------------------------------------------------
    */

    protected static function evaluate(string $__path, array $__data): string
    {
        extract($__data, EXTR_SKIP);

        ob_start();

        try {
            include $__path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    public static function extend(string $layout): void
    {
        static::$layout = $layout;
    }

    /** @section('name') atau @section('name', 'isi singkat') */
    public static function startSection(string $name, mixed $content = null): void
    {
        if ($content !== null) {
            static::$sections[$name] ??= $content instanceof \Closure ? $content() : (string) $content;

            return;
        }

        static::$sectionStack[] = $name;
        ob_start();
    }

    /** Menutup section dan mengembalikan namanya (dipakai oleh @show). */
    public static function stopSection(): string
    {
        if (! static::$sectionStack) {
            throw new \RuntimeException('@endsection dipanggil tanpa @section pembuka.');
        }

        $name    = array_pop(static::$sectionStack);
        $content = (string) ob_get_clean();

        // Section anak didaftarkan lebih dulu, jadi definisi di layout
        // hanya berlaku sebagai nilai bawaan.
        if (isset(static::$sections[$name])) {
            static::$sections[$name] = str_replace(
                static::parentPlaceholder(),
                $content,
                static::$sections[$name]
            );
        } else {
            static::$sections[$name] = $content;
        }

        return $name;
    }

    public static function yieldContent(string $name, string $default = ''): string
    {
        $content = static::$sections[$name] ?? $default;

        return str_replace(static::parentPlaceholder(), '', $content);
    }

    public static function parentContent(): string
    {
        return static::parentPlaceholder();
    }

    protected static function parentPlaceholder(): string
    {
        return '##batara-parent##';
    }

    /** Render view lain di dalam view (@include). */
    public static function insert(array $variables, string $view, array $data = []): string
    {
        unset($variables['__path'], $variables['__data'], $variables['this']);

        // Simpan status layout supaya include tidak mengganggu view induk.
        $layout         = static::$layout;
        static::$layout = null;

        $content = static::evaluate(
            static::compiled($view),
            array_merge(static::$shared, $variables, $data)
        );

        static::$layout = $layout;

        return $content;
    }

    public static function insertIf(array $variables, string $view, array $data = []): string
    {
        return static::exists($view) ? static::insert($variables, $view, $data) : '';
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }

    public static function methodField(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}


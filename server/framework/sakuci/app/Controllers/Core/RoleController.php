<?php

namespace App\Controllers\Core;

use App\Models\Role;
use App\Models\User;
use Sakuci\Controller;
use Sakuci\Http\Request;

/**
 * Halaman admin untuk mengelola role: tambah, ganti nama, hapus, dan atur
 * apakah role tersebut boleh dipakai untuk registrasi mandiri (/register).
 *
 * Role "admin" adalah bawaan dan tidak bisa diganti nama / dihapus lewat
 * panel ini. Role lain dibuat sendiri oleh admin -- middleware "{Role}Only"
 * dan route group "/nama-role" dibuat & dihapus otomatis mengikuti role-nya.
 */
class RoleController extends Controller
{
    protected const PROTECTED_ROLE = 'admin';

    /** Penanda di komentar berkas middleware supaya aman dihapus otomatis nanti. */
    protected const GENERATED_MARKER = 'Dibuat otomatis oleh RoleController';

    public function index()
    {
        return view('core.admin.roles.index', [
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|alpha_dash|max:50',
        ]);

        $name = strtolower($data['name']);

        if (Role::firstWhere('name', $name)) {
            return back()->withErrors(['name' => 'Role "' . $name . '" sudah ada.'])->withInput();
        }

        Role::create([
            'name'         => $name,
            'can_register' => $request->boolean('can_register'),
        ]);

        $this->ensureMiddlewareFor($name);
        $this->ensureRouteFor($name);

        return back()->with('success', 'Role "' . $name . '" berhasil ditambahkan. Middleware & route-nya otomatis dibuat: ->middleware(\'' . $name . '\') atau buka /' . $name . '.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === static::PROTECTED_ROLE) {
            abort(403, 'Role "admin" tidak bisa diubah.');
        }

        $data = $request->validate([
            'name' => 'required|alpha_dash|max:50',
        ]);

        $oldName = $role->name;
        $newName = strtolower($data['name']);

        if ($newName !== $oldName && Role::firstWhere('name', $newName)) {
            return back()->withErrors(['name' => 'Role "' . $newName . '" sudah ada.']);
        }

        $role->update([
            'name'         => $newName,
            'can_register' => $request->boolean('can_register'),
        ]);

        if ($newName !== $oldName) {
            User::where('role', $oldName)->update(['role' => $newName]);

            $this->removeRouteFor($oldName);
            $this->removeMiddlewareFor($oldName);

            $this->ensureMiddlewareFor($newName);
            $this->ensureRouteFor($newName);
        }

        return back()->with('success', 'Role "' . $oldName . '" berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === static::PROTECTED_ROLE) {
            abort(403, 'Role "admin" tidak bisa dihapus.');
        }

        $pemakai = User::where('role', $role->name)->count();

        $name = $role->name;
        User::where('role', $name)->delete();
        $role->delete();

        $this->removeRouteFor($name);
        $this->removeMiddlewareFor($name);

        $message = 'Role "' . $name . '" berhasil dihapus.';
        $message .= $pemakai > 0 ? ' ' . $pemakai . ' user pemilik role ini ikut dihapus.' : '';

        return back()->with('success', $message);
    }

    /*
    |----------------------------------------------------------------------
    | Middleware otomatis
    |----------------------------------------------------------------------
    */

    /** Buat berkas middleware "{Role}Only" (bila belum ada) lalu daftarkan aliasnya. */
    protected function ensureMiddlewareFor(string $roleName): void
    {
        $studly = str_studly($roleName) . 'Only';
        $fqcn   = 'App\\Middleware\\' . $studly;
        $path   = app_path('Middleware/' . $studly . '.php');

        if (! is_file($path)) {
            $marker = static::GENERATED_MARKER;

            file_put_contents($path, <<<PHP
            <?php

            namespace App\Middleware;

            /** {$marker} -- hanya role "{$roleName}" yang boleh lewat. */
            class {$studly} extends EnsureRole
            {
                protected function roles(): array
                {
                    return ['{$roleName}'];
                }
            }

            PHP);
        }

        $this->registerMiddlewareAlias($roleName, $fqcn);
    }

    /** Hapus berkas middleware & aliasnya, tapi hanya bila memang dibuat otomatis. */
    protected function removeMiddlewareFor(string $roleName): void
    {
        $this->removeMiddlewareAlias($roleName);

        $studly = str_studly($roleName) . 'Only';
        $path   = app_path('Middleware/' . $studly . '.php');

        if (is_file($path) && str_contains((string) file_get_contents($path), static::GENERATED_MARKER)) {
            unlink($path);
        }
    }

    /** Tambahkan alias baru ke array 'middleware' di config/app.php, bila belum ada. */
    protected function registerMiddlewareAlias(string $alias, string $fqcn): void
    {
        $path    = base_path('config/app.php');
        $content = file_get_contents($path);

        if ($content === false || str_contains($content, "'{$alias}' =>")) {
            return;
        }

        $needle      = "'middleware' => [";
        $replacement = $needle . "\n        '{$alias}' => {$fqcn}::class,";

        $content = str_replace($needle, $replacement, $content, $count);

        if ($count > 0) {
            file_put_contents($path, $content);
        }
    }

    /** Buang satu baris alias dari array 'middleware' di config/app.php. */
    protected function removeMiddlewareAlias(string $alias): void
    {
        $path    = base_path('config/app.php');
        $content = file_get_contents($path);

        if ($content === false) {
            return;
        }

        $pattern = '/\n[ \t]*\'' . preg_quote($alias, '/') . '\'\s*=>\s*\\\\?App\\\\Middleware\\\\\w+::class,/';
        $updated = preg_replace($pattern, '', $content, 1);

        if ($updated !== null && $updated !== $content) {
            file_put_contents($path, $updated);
        }
    }

    /*
    |----------------------------------------------------------------------
    | Route otomatis
    |----------------------------------------------------------------------
    | Ditambahkan/dihapus di antara marker "@generated-roles" pada
    | routes/web.php, ditandai per-role dengan "@role:{nama}".
    */

    protected function ensureRouteFor(string $roleName): void
    {
        $path    = base_path('routes/web.php');
        $content = file_get_contents($path);

        if ($content === false || str_contains($content, "// @role:{$roleName}:start")) {
            return;
        }

        $block = "// @role:{$roleName}:start\n"
            . "Route::group(['prefix' => '{$roleName}', 'middleware' => '{$roleName}'], function () {\n"
            . "    Route::get('/', [DashboardController::class, 'index'])->name('{$roleName}.dashboard');\n"
            . "});\n"
            . "// @role:{$roleName}:end\n";

        $updated = str_replace('// @generated-roles:end', $block . '// @generated-roles:end', $content, $count);

        if ($count > 0) {
            file_put_contents($path, $updated);
        }
    }

    protected function removeRouteFor(string $roleName): void
    {
        $path    = base_path('routes/web.php');
        $content = file_get_contents($path);

        if ($content === false) {
            return;
        }

        $pattern = '/\n?\/\/ @role:' . preg_quote($roleName, '/') . ':start.*?\/\/ @role:' . preg_quote($roleName, '/') . ':end\n?/s';
        $updated = preg_replace($pattern, "\n", $content, 1);

        if ($updated !== null && $updated !== $content) {
            file_put_contents($path, $updated);
        }
    }
}

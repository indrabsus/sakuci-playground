<?php

namespace App\Controllers;

use Sakuci\Controller;
use Sakuci\Http\Request;
use App\Models\Mahasiswa;

class MahasiswaController extends Controller
{
    /**
     * [READ] Tampilkan daftar seluruh mahasiswa
     */
    public function index()
    {
        $daftarMahasiswa = Mahasiswa::all();

        return view('mahasiswa.index', [
            'mahasiswa' => $daftarMahasiswa
        ]);
    }

    /**
     * [CREATE] Tampilkan form tambah mahasiswa
     */
    public function create()
    {
        return view('mahasiswa.create');
    }

    /**
     * [STORE] Simpan data mahasiswa baru
     */
    public function store(Request $request)
    {
        $nim = trim((string) $request->input('nim', ''));
        $nama = trim((string) $request->input('nama', ''));
        $jurusan = trim((string) $request->input('jurusan', ''));
        $email = trim((string) $request->input('email', ''));
        $ipk = (float) $request->input('ipk', 0.0);

        if ($nim === '' || $nama === '') {
            return $this->back()->with('error', 'NIM dan Nama Mahasiswa wajib diisi!');
        }

        Mahasiswa::create([
            'nim' => $nim,
            'nama' => $nama,
            'jurusan' => $jurusan,
            'email' => $email,
            'ipk' => $ipk,
        ]);

        return $this->redirect('/mahasiswa')->with('pesan', "Mahasiswa {$nama} berhasil ditambahkan!");
    }

    /**
     * [EDIT] Tampilkan form edit mahasiswa berdasarkan ID
     */
    public function edit(mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if (!$mhs) {
            return $this->redirect('/mahasiswa')->with('error', 'Data mahasiswa tidak ditemukan!');
        }

        return view('mahasiswa.edit', [
            'mhs' => $mhs
        ]);
    }

    /**
     * [UPDATE] Perbarui data mahasiswa di database
     */
    public function update(Request $request, mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if (!$mhs) {
            return $this->redirect('/mahasiswa')->with('error', 'Data mahasiswa tidak ditemukan!');
        }

        $mhs->update([
            'nim' => trim((string) $request->input('nim', $mhs->nim)),
            'nama' => trim((string) $request->input('nama', $mhs->nama)),
            'jurusan' => trim((string) $request->input('jurusan', $mhs->jurusan)),
            'email' => trim((string) $request->input('email', $mhs->email)),
            'ipk' => (float) $request->input('ipk', $mhs->ipk),
        ]);

        return $this->redirect('/mahasiswa')->with('pesan', "Data {$mhs->nama} berhasil diperbarui!");
    }

    /**
     * [DELETE] Hapus data mahasiswa
     */
    public function destroy(mixed $id)
    {
        $mhs = Mahasiswa::find($id);

        if ($mhs) {
            $nama = $mhs->nama;
            $mhs->delete();
            return $this->redirect('/mahasiswa')->with('pesan', "Mahasiswa {$nama} berhasil dihapus!");
        }

        return $this->redirect('/mahasiswa')->with('error', 'Data tidak ditemukan!');
    }
}

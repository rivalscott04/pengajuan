<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EmployeeService
{
    /**
     * Cek koneksi ke API pegawai dengan memanggil endpoint search.
     *
     * @return array{ok: bool, status: int|null, count: int|null, message: string|null}
     */
    public function ping(): array
    {
        $baseUrl = rtrim(config('services.employee_api.base_url'), '/');
        if (empty($baseUrl)) {
            return [
                'ok' => false,
                'status' => null,
                'count' => null,
                'message' => 'EMPLOYEE_API_BASE_URL belum diatur.',
            ];
        }

        // Coba tanpa query parameter dulu, kalau gagal baru pakai query sederhana
        $response = Http::withHeaders($this->headers())
            ->withoutVerifying()
            ->acceptJson()
            ->get($baseUrl.'/api/public/employees');

        // Kalau tanpa query gagal, coba dengan query sederhana
        if ($response->failed()) {
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->acceptJson()
                ->get($baseUrl.'/api/public/employees', [
                    'q' => 'a',
                ]);
        }

        if ($response->failed()) {
            return [
                'ok' => false,
                'status' => $response->status(),
                'count' => null,
                'message' => 'Gagal menghubungi API pegawai (HTTP '.$response->status().'). Pastikan endpoint benar: '.$baseUrl.'/api/public/employees',
            ];
        }

        $json = $response->json();
        
        // Handle struktur response
        if (!isset($json['success']) || !$json['success']) {
            return [
                'ok' => false,
                'status' => $response->status(),
                'count' => null,
                'message' => 'API mengembalikan success=false atau format tidak sesuai.',
            ];
        }

        $data = $json['data']['data'] ?? [];
        $total = $json['data']['total'] ?? 0;
        $count = is_array($data) ? count($data) : 0;

        return [
            'ok' => true,
            'status' => $response->status(),
            'count' => $count,
            'message' => null,
        ];
    }

    /**
     * Ambil 1 pegawai berdasarkan NIP (detail).
     * Membaca dari database lokal (tabel employees).
     */
    public function findByNip(string $nip): ?array
    {
        // Cari dari database lokal berdasarkan NIP atau NIP_BARU
        $employee = Employee::query()
            ->where('nip', $nip)
            ->orWhere('nip_baru', $nip)
            ->first();

        if (!$employee) {
            return null;
        }

        // Ambil tanggal lahir dari raw data jika ada
        $raw = $employee->raw ?? [];
        $tanggalLahir = null;
        if (isset($raw['TGL_LAHIR'])) {
            $tanggalLahir = $raw['TGL_LAHIR'];
            if ($tanggalLahir && strpos($tanggalLahir, 'T') !== false) {
                $tanggalLahir = substr($tanggalLahir, 0, 10);
            }
        }

        // Format TMT pangkat
        $tmtPangkat = $employee->tmt_pangkat ? $employee->tmt_pangkat->format('Y-m-d') : null;

        return [
            'employee_id' => $employee->id,
            'employee_external_id' => $employee->nip_baru ?? $employee->nip,
            'nip' => $employee->nip_baru ?? $employee->nip,
            'applicant_name' => $employee->nama_lengkap ?? '',
            'satuan_kerja' => $employee->satuan_kerja ?? '',
            'jabatan' => $employee->jabatan ?? '',
            'pangkat_sekarang' => $employee->pangkat_asn ?? $employee->gol_ruang ?? '',
            'tmt_pangkat' => $tmtPangkat,
            'masa_kerja' => ($employee->mk_tahun ?? 0).' tahun '.($employee->mk_bulan ?? 0).' bulan',
            'pendidikan' => $employee->jenjang_pendidikan ?? '',
            'tanggal_lahir' => $tanggalLahir,
            'kab_kota' => $employee->kab_kota ?? '',
            'satker_kode' => $employee->kode_satuan_kerja ?? '',
        ];
    }

    /**
     * Cari pegawai berdasarkan NIP atau nama (untuk combobox searchable).
     * Membaca dari database lokal (tabel employees).
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query, int $maxResults = 100): array
    {
        $searchTerm = trim($query);
        
        if (empty($searchTerm)) {
            return [];
        }

        $searchPattern = $searchTerm . '%';
        $searchPatternAnywhere = '%' . $searchTerm . '%';
        
        $employees = Employee::query()
            ->where(function ($q) use ($searchPattern, $searchPatternAnywhere) {
                $q->where('nip', 'LIKE', $searchPattern)
                    ->orWhere('nip_baru', 'LIKE', $searchPattern)
                    ->orWhere('nama_lengkap', 'LIKE', $searchPattern)
                    ->orWhere('nama_lengkap', 'LIKE', $searchPatternAnywhere);
            })
            ->whereNotNull('nip')
            ->whereNotNull('nama_lengkap')
            ->where('nip', '!=', '')
            ->where('nama_lengkap', '!=', '')
            ->limit($maxResults)
            ->get();

        return $employees->map(function (Employee $employee) {
            // Ambil tanggal lahir dari raw data jika ada
            $raw = $employee->raw ?? [];
            $tanggalLahir = $raw['TGL_LAHIR'] ?? null;
            if ($tanggalLahir && strpos($tanggalLahir, 'T') !== false) {
                $tanggalLahir = substr($tanggalLahir, 0, 10);
            }

            // Format TMT pangkat
            $tmtPangkat = $employee->tmt_pangkat ? $employee->tmt_pangkat->format('Y-m-d') : null;

            // Pastikan NIP tidak kosong
            $nip = !empty($employee->nip_baru) ? $employee->nip_baru : $employee->nip;
            $nama = $employee->nama_lengkap ?? '';
            
            // Skip kalau NIP atau nama kosong
            if (empty($nip) || empty($nama)) {
                return null;
            }
            
            return [
                'employee_id' => $employee->id,
                'employee_external_id' => $nip,
                'nip' => $nip,
                'applicant_name' => $nama,
                'satuan_kerja' => $employee->satuan_kerja ?? '',
                'jabatan' => $employee->jabatan ?? '',
                'pangkat_sekarang' => $employee->pangkat_asn ?? $employee->gol_ruang ?? '',
                'tmt_pangkat' => $tmtPangkat,
                'masa_kerja' => ($employee->mk_tahun ?? 0).' tahun '.($employee->mk_bulan ?? 0).' bulan',
                'pendidikan' => $employee->jenjang_pendidikan ?? '',
                'tanggal_lahir' => $tanggalLahir,
                'kab_kota' => $employee->kab_kota ?? '',
                'satker_kode' => $employee->kode_satuan_kerja ?? '',
            ];
        })
        ->filter() // Hapus null values
        ->values()
        ->all();
    }

    public function headers(): array
    {
        $token = config('services.employee_api.token');

        return array_filter([
            'Authorization' => $token ? 'Bearer '.$token : null,
        ]);
    }
}


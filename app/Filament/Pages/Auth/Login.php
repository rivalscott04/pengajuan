<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable | null
    {
        return 'KP Kemenag NTB';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Masuk sebagai Admin / Operator';
    }

    /**
     * Izinkan login menggunakan email atau username di field yang sama.
     *
     * Field form bawaan Filament bernama "email", tapi nilainya bisa berisi
     * alamat email atau username.
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $login = $data['email'] ?? '';

        // Jika formatnya email, pakai kolom email, kalau bukan, pakai kolom username
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return [
            $field => $login,
            'password' => $data['password'] ?? null,
        ];
    }

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();
        
        if ($user) {
            // Redirect ke panel yang sesuai dengan role
            if ($user->hasRole('admin')) {
                return route('filament.admin.pages.dashboard');
            } elseif ($user->hasAnyRole(['operator_kabkota', 'operator_kanwil'])) {
                return route('filament.operator.pages.dashboard');
            }
        }
        
        return parent::getRedirectUrl();
    }
}


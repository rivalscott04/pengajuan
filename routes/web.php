<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            return redirect('/admin');
        } elseif ($user->hasAnyRole(['operator_kabkota', 'operator_kanwil'])) {
            return redirect('/operator');
        }
    }
    return view('welcome');
});

// Route login terpisah
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    Route::post('/login', function (\Illuminate\Http\Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['email'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $login, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            
            // Update password hash di session supaya tidak dianggap invalid oleh AuthenticateSession
            $defaultGuard = config('auth.defaults.guard', 'web');
            session()->put('password_hash_'.$defaultGuard, $user->getAuthPassword());
            
            // Redirect ke panel yang sesuai dengan role menggunakan URL langsung
            if ($user->hasRole('admin')) {
                return redirect('/admin');
            } elseif ($user->hasAnyRole(['operator_kabkota', 'operator_kanwil'])) {
                return redirect('/operator');
            }
            
            return redirect('/admin'); // Fallback ke admin
        }

        return back()->withErrors([
            'email' => 'Kredensial yang diberikan tidak cocok dengan data kami.',
        ])->onlyInput('email');
    });
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/impersonate/{id}', function ($id) {
        $user = auth()->user();

        abort_unless($user, 403);

        $target = User::findOrFail($id);

        abort_unless(
            method_exists($user, 'canImpersonate')
            && method_exists($target, 'canBeImpersonated')
            && $user->canImpersonate()
            && $target->canBeImpersonated(),
            403
        );

        // Simpan ID admin yang meng-impersonate (hanya sekali)
        if (! session()->has('impersonator_id')) {
            session(['impersonator_id' => $user->id]);
        }

        // Login sebagai user target di guard web & guard Filament
        auth()->login($target);
        Filament::auth()->login($target);

        // Update password hash di session supaya tidak dianggap invalid oleh AuthenticateSession
        $defaultGuard = config('auth.defaults.guard', 'web');
        session()->put('password_hash_'.$defaultGuard, $target->getAuthPassword());

        $filamentGuard = config('filament.auth.guard', $defaultGuard);
        if ($filamentGuard !== $defaultGuard) {
            session()->put('password_hash_'.$filamentGuard, $target->getAuthPassword());
        }

        // Redirect ke panel yang sesuai dengan role
        $targetPanel = $target->hasRole('admin') ? 'admin' : 'operator';
        return redirect()->route("filament.{$targetPanel}.pages.dashboard");
    })->name('impersonate.start');

    Route::get('/impersonate/leave', function () {
        if (session()->has('impersonator_id')) {
            $originalId = session('impersonator_id');
            session()->forget('impersonator_id');

            if ($originalId) {
                $original = User::find($originalId);

                if ($original) {
                    auth()->login($original);
                    Filament::auth()->login($original);

                    $defaultGuard = config('auth.defaults.guard', 'web');
                    session()->put('password_hash_'.$defaultGuard, $original->getAuthPassword());

                    $filamentGuard = config('filament.auth.guard', $defaultGuard);
                    if ($filamentGuard !== $defaultGuard) {
                        session()->put('password_hash_'.$filamentGuard, $original->getAuthPassword());
                    }
                    
                    // Redirect ke panel yang sesuai dengan role
                    $originalPanel = $original->hasRole('admin') ? 'admin' : 'operator';
                    return redirect()->route("filament.{$originalPanel}.pages.dashboard");
                }
            }
        }

        // Fallback: redirect ke admin jika tidak ada original user
        return redirect()->route('filament.admin.pages.dashboard');
    })->name('impersonate.leave');
});

// Route untuk download dokumen submission
Route::middleware(['auth'])->group(function () {
    Route::get('/submission/document/{document}/download', function (\App\Models\SubmissionDocument $document) {
        $user = auth()->user();
        $submission = $document->submission;
        
        // Cek permission
        if ($user->hasRole('admin') || $user->hasRole('operator_kanwil')) {
            // Admin dan operator_kanwil bisa akses semua
        } elseif ($user->hasRole('operator_kabkota')) {
            // Operator kabkota hanya bisa akses submission dari region mereka
            if ($submission->region_id !== $user->region_id) {
                abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
            }
        } else {
            abort(403, 'Anda tidak memiliki akses ke dokumen ini.');
        }
        
        $path = \Illuminate\Support\Facades\Storage::disk('private')->path($document->path);
        
        if (!file_exists($path)) {
            abort(404, 'Dokumen tidak ditemukan.');
        }
        
        return response()->download($path, basename($document->path));
    })->name('submission.document.download');

    Route::post('/submission/document/{document}/toggle-verification', function (\App\Models\SubmissionDocument $document) {
        $user = auth()->user();
        $submission = $document->submission;

        // Hanya operator kanwil (dan admin opsional) yang boleh memverifikasi dokumen
        if (! $user->hasRole('operator_kanwil') && ! $user->hasRole('admin')) {
            abort(403, 'Anda tidak memiliki akses untuk memverifikasi dokumen ini.');
        }

        $document->is_verified = ! $document->is_verified;
        $document->save();

        return response()->json([
            'success' => true,
            'is_verified' => $document->is_verified,
        ]);
    })->name('submission.document.toggleVerification');
});


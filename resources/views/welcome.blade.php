<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Pengajuan Internal - Kemenag NTB</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    <!-- Vite Styles -->
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-white text-slate-900">
    <div class="relative min-h-screen flex flex-col">
        <!-- Header/Navbar -->
        <nav class="border-b border-slate-100">
            <div class="max-w-7xl mx-auto px-6 py-4 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-emerald-600 p-1.5 rounded-lg shadow-sm">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight text-slate-800 block leading-tight">Pengajuan</span>
                        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest">Kemenag Provinsi NTB</span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/admin') }}" class="text-sm font-semibold text-slate-600 hover:text-emerald-600 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2 text-sm font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition-all">
                                Masuk
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative flex-1 flex flex-col justify-center overflow-hidden min-h-[85vh]">
            <!-- Full Hero Image with Overlay -->
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=2000" 
                     alt="Professional Office" 
                     class="w-full h-full object-cover opacity-20">
                <div class="absolute inset-0 bg-gradient-to-b from-white via-white/80 to-white"></div>
            </div>

            <!-- Content Area -->
            <div class="relative z-10 max-w-7xl mx-auto px-6 py-20 w-full">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-8">
                        Layanan Kepegawaian Digital
                    </div>

                    <h1 class="text-5xl md:text-7xl font-extrabold text-slate-900 tracking-tight mb-8 leading-[1.1]">
                        Portal <br/>
                        <span class="text-emerald-600">Pengajuan</span> <br/>
                        Internal
                    </h1>
                    
                    <p class="text-lg md:text-xl text-slate-600 leading-relaxed mb-12 max-w-xl">
                        Akses layanan Kenaikan Pangkat dan Pencantuman Gelar dengan lebih mudah, cepat, dan transparan dalam satu pintu.
                    </p>

                    <div class="flex flex-col sm:flex-row items-start gap-4">
                        <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-4 text-lg font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition-all shadow-xl shadow-emerald-100">
                            Masuk & Ajukan Sekarang
                        </a>
                        <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-4 text-lg font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Pelajari Selengkapnya
                        </a>
                    </div>

                    <!-- Trust Badges / Stats -->
                    <div class="mt-16 grid grid-cols-2 md:grid-cols-3 gap-8 border-t border-slate-100 pt-8">
                        <div>
                            <span class="block text-2xl font-bold text-slate-900">Digital</span>
                            <span class="text-sm text-slate-500">Tanpa Berkas Fisik</span>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900">Real-time</span>
                            <span class="text-sm text-slate-500">Pantau Status Online</span>
                        </div>
                        <div class="hidden md:block">
                            <span class="block text-2xl font-bold text-slate-900">Terpusat</span>
                            <span class="text-sm text-slate-500">Satu Pintu Layanan</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Features Section -->
    <section id="features" class="py-24 bg-slate-50 border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20">
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 mb-4">Layanan Unggulan Kami</h2>
                <div class="h-1.5 w-20 bg-emerald-500 mx-auto rounded-full"></div>
            </div>

            <div class="grid md:grid-cols-2 gap-8 lg:gap-12">
                <!-- Feature 1: Kenaikan Pangkat -->
                <div class="group p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-white hover:shadow-2xl hover:shadow-emerald-100 hover:-translate-y-1">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-8 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Kenaikan Pangkat</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Ajukan kenaikan pangkat secara digital tanpa perlu berkas fisik yang menumpuk. Pantau status pengajuan Anda secara real-time.
                    </p>
                </div>

                <!-- Feature 2: Penyematan Gelar -->
                <div class="group p-10 rounded-3xl bg-slate-50 border border-slate-100 transition-all hover:bg-white hover:shadow-2xl hover:shadow-emerald-100 hover:-translate-y-1">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-8 transition-colors group-hover:bg-emerald-600 group-hover:text-white">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mb-4">Penyematan Gelar</h3>
                    <p class="text-slate-600 leading-relaxed">
                        Proses pengajuan pencantuman gelar pendidikan formal terbaru Anda dengan persyaratan yang jelas dan alur yang mudah.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white py-12 border-t border-slate-100 text-slate-600">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-3">
                <div class="bg-slate-100 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="text-left">
                    <span class="text-sm font-bold text-slate-800 block uppercase tracking-wider">Pengajuan Kemenag NTB</span>
                    <span class="text-[10px] text-slate-500 font-medium">© {{ date('Y') }} Kantor Wilayah Kementerian Agama Provinsi NTB</span>
                </div>
            </div>
            
            <div class="flex gap-8 text-sm font-semibold">
                <a href="#" class="hover:text-emerald-600 transition-colors">Panduan</a>
                <a href="#" class="hover:text-emerald-600 transition-colors">Kebijakan</a>
                <a href="#" class="hover:text-emerald-600 transition-colors">Bantuan</a>
            </div>
        </div>
    </footer>
</body>
</html>

{{-- ======================================== FILE:
resources/views/auth/login.blade.php FUNGSI: Halaman form login
======================================== --}} @extends('layouts.app') {{-- ↑
Menggunakan layout dari layouts/app.blade.php Halaman ini akan "masuk" ke bagian
@yield('content') --}} @section('content') {{-- ↑ Mulai section yang akan
ditampilkan di @yield('content') --}}

<div class="container">
  <div class="row justify-content-center">
    {{-- ↑ justify-content-center = posisikan di tengah horizontal --}}

    <div class="col-md-6">
      {{-- ↑ col-md-6 = lebar 50% di layar medium ke atas --}}

      <div class="card shadow-sm">
        {{-- Card Header --}}
        <div class="card-header bg-primary text-white text-center">
          <h4 class="mb-0">🔐 Login ke Akun Anda</h4>
        </div>

        <div class="card-body p-4">
          {{-- ================================================ FORM LOGIN
          ================================================ method="POST" = Kirim
          data secara aman (tidak terlihat di URL) action = URL tujuan submit
          form ================================================ --}}
          <form method="POST" action="{{ route('login') }}">
            {{-- ================================================ CSRF TOKEN
            ================================================ @csrf WAJIB ada di
            setiap form POST/PUT/DELETE Ini adalah proteksi keamanan dari
            Laravel ================================================ --}} @csrf
            {{-- ================== FIELD EMAIL ================== --}}
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>

              <input id="email" type="email" class="form-control @error('email')
              is-invalid @enderror" {{-- ↑ @error('email') = jika ada error pada
              field email, tambahkan class 'is-invalid' untuk styling merah --}}
              name="email" value="{{ old('email') }}" {{-- ↑ old('email') = isi
              kembali nilai sebelumnya jika form gagal validasi --}} required
              autocomplete="email" autofocus placeholder="nama@email.com"> {{--
              Tampilkan pesan error jika ada --}} @error('email')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            {{-- ================== FIELD PASSWORD ================== --}}
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>

              <input
                id="password"
                type="password"
                {{--
                ↑
                type="password"
                ="karakter"
                akan
                disembunyikan
                (●●●●)
                --}}
                class="form-control @error('password') is-invalid @enderror"
                name="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
              />

              @error('password')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
              @enderror
            </div>

            {{-- ================== CHECKBOX REMEMBER ME ================== --}}
            <div class="mb-3 form-check">
              <input class="form-check-input" type="checkbox" name="remember"
              id="remember" {{ old('remember') ? 'checked' : '' }}> {{-- ↑ Jika
              sebelumnya dicentang, tetap centang --}}

              <label class="form-check-label" for="remember">
                Ingat Saya
              </label>
            </div>
            {{-- ↑ "Ingat Saya" = Simpan session lebih lama (tidak logout
            otomatis) --}} {{-- ================== TOMBOL SUBMIT
            ================== --}}
            <div class="d-grid gap-2">
              {{-- ↑ d-grid = display grid, membuat button full width --}}
              <button type="submit" class="btn btn-primary btn-lg">
                Login
              </button>
            </div>

            {{-- ================== LINK LUPA PASSWORD ================== --}}
            <div class="mt-3 text-center">
              @if (Route::has('password.request'))
              <a
                class="text-decoration-none"
                href="{{ route('password.request') }}"
              >
                Lupa Password?
              </a>
              @endif
            </div>

           {{-- ======================================== Tambahkan setelah tombol Login
biasa ======================================== --}}
{{-- ======================================== Tambahkan setelah tombol Login
biasa ======================================== --}}

<hr class="my-4" />
{{-- ↑ Garis pemisah --}} {{-- ================================================
TOMBOL LOGIN DENGAN GOOGLE ================================================ --}}
<div class="d-grid gap-2">
  <a href="{{ route('auth.google') }}" class="btn btn-outline-danger btn-lg">
    {{-- ↑ route('auth.google') = URL /auth/google btn-outline-danger = warna
    merah (Google brand) --}} {{-- Google Icon SVG --}}
    <svg class="me-2" width="20" height="20" viewBox="0 0 24 24">
      <path
        fill="#4285F4"
        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
      />
      <path
        fill="#34A853"
        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
      />
      <path
        fill="#FBBC05"
        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
      />
      <path
        fill="#EA4335"
        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
      />
    </svg>

    Login dengan Google
  </a>
</div>

{{-- Teks alternatif register --}}
<p class="mt-4 text-center mb-0">
  Belum punya akun?
  <a href="{{ route('register') }}" class="text-decoration-none fw-bold">
    Daftar Sekarang
  </a>
</p>

            {{-- ================== LINK REGISTER ================== --}}
           
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection {{-- ↑ Akhir dari section content --}}
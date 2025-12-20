<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     public function cart()
    {
        return $this->hasOne(Cart::class);
    }

     public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

     public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Relasi many-to-many ke products melalui wishlists.
     */
    public function wishlistProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists')
                    ->withTimestamps();
    }

    // ==================== HELPER METHODS ====================

    /**
     * Cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user adalah customer.
     */
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    /**
     * Cek apakah produk ada di wishlist user.
     */
    public function hasInWishlist(Product $product): bool
    {
        return $this->wishlists()
                    ->where('product_id', $product->id)
                    ->exists();
    }

// app/Models/User.php

// Tambahkan accessor untuk avatar URL

/**
 * Get the avatar URL.
 * Accessor ini otomatis dipanggil saat kita akses $user->avatar_url
 * Logika Prioritas:
 * 1. Cek Storage Lokal: Apakah user upload file custom? Jika ya, return URL local storage.
 * 2. Cek URL Eksternal: Apakah user login via Google? Jika ya, return URL dari Google.
 * 3. Fallback: Gunakan Gravatar berdasarkan hash email agar user tidak tampil polos.
 */
public function getAvatarUrlAttribute(): string
{
    // Prioritas 1: Avatar yang di-upload (file fisik ada di server)
    // Kita harus cek Storage::exists() agar tidak broken image jika file-nya terhapus manual.
    if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
        return asset('storage/' . $this->avatar);
    }

    // Prioritas 2: Avatar dari Google (URL eksternal dimulai dengan http)
    // Biasanya ini terjadi saat user login via Socialite (Google Sign-In).
    if (str_starts_with($this->avatar ?? '', 'http')) {
        return $this->avatar;
    }

    // Prioritas 3: Gravatar (Layanan sedunia untuk avatar berdasarkan email)
    // Gravatar menggunakan MD5 hash dari email lowercase.
    // Jika user belum punya gravatar, tampilkan 'mp' (Mystery Person).
    // &s=200 artinya size gambar 200x200px.
    $hash = md5(strtolower(trim($this->email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=mp&s=200";
}

/**
 * Get initials from name for avatar fallback.
 * Contoh: "Agung Wahyudi" -> "AW"
 * Berguna jika kita ingin membuat UI avatar berupa inisial huruf teks.
 */
public function getInitialsAttribute(): string
{
    $words = explode(' ', $this->name);
    $initials = '';

    foreach ($words as $word) {
        // Ambil huruf pertama tiap kata dan kapitalkan
        $initials .= strtoupper(substr($word, 0, 1));
    }

    // Ambil maksimal 2 huruf pertama saja
    return substr($initials, 0, 2);
}
}




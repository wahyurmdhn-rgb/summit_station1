<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ReturnRecord;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Beranda Publik dengan Ulasan Pelanggan Dinamis
     */
    public function index(Request $request): View
    {
        $reviews = collect();
        $totalReviewsCount = 0;
        $averageRating = 0.0;

        try {
            $reviews = Review::visible()
                ->with(['user', 'product', 'bundle', 'order'])
                ->orderBy('created_at', 'desc')
                ->get();

            $totalReviewsCount = Review::visible()->count();
            $averageRating = $totalReviewsCount > 0
                ? round((float) Review::visible()->avg('rating'), 1)
                : 0.0;
        } catch (\Throwable $e) {
            // Fallback: reviews table might be missing columns
            try {
                $reviews = Review::with(['user', 'product'])
                    ->orderBy('created_at', 'desc')
                    ->get();
                $totalReviewsCount = Review::count();
                $averageRating = $totalReviewsCount > 0
                    ? round((float) Review::avg('rating'), 1)
                    : 0.0;
            } catch (\Throwable $e2) {
                // Table doesn't exist or is completely broken
            }
        }

        return view('home.index', [
            'accountName' => session('account_name'),
            'accountRole' => session('account_role'),
            'reviews' => $reviews,
            'totalReviewsCount' => $totalReviewsCount,
            'averageRating' => $averageRating,
            'featuredProducts' => $this->featuredProducts(),
        ]);
    }

    /**
     * Produk unggulan untuk beranda user (data dari database yang sama).
     */
    protected function featuredProducts(): \Illuminate\Support\Collection
    {
        try {
            return Product::with('category')
                ->where('is_active', true)
                ->where('stock_available', '>', 0)
                ->orderByDesc('rating')
                ->orderByDesc('reviews_count')
                ->limit(8)
                ->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    /**
     * Profil Pengguna (Member Portal)
     */
    public function profile(Request $request): View|RedirectResponse
    {
        if (! session()->has('account_id') || session('account_role') !== 'customer') {
            return redirect()->route('login')
                ->with('status', 'Silakan masuk terlebih dahulu untuk mengakses profil Anda.');
        }

        $user = User::withCount('orders')->find(session('account_id'));
        if (! $user) {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Sesi pengguna tidak valid.']);
        }

        if ($user->status === 'suspended') {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Silakan hubungi Administrator.']);
        }

        if ($user->status === 'inactive') {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda nonaktif. Silakan hubungi Administrator.']);
        }

        $activeOrdersCount = Order::where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending', 'paid'])
            ->count();

        $completedOrdersCount = Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $totalOrdersCount = Order::where('user_id', $user->id)->count();

        $returnsCount = ReturnRecord::whereHas('order', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        $latestOrder = Order::with(['items.product', 'payments', 'returns', 'review', 'refunds', 'latePenalty'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->first();

        return view('home.profile', compact(
            'user',
            'activeOrdersCount',
            'completedOrdersCount',
            'totalOrdersCount',
            'returnsCount',
            'latestOrder'
        ));
    }

    /**
     * Update Informasi Profil Pengguna
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        if (! session()->has('account_id') || session('account_role') !== 'customer') {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session('account_id'));

        if ($user->status === 'suspended' || $user->status === 'inactive') {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED).']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:50|unique:users,username,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'avatar' => 'nullable|image|max:5120',
        ]);

        // Domisili hanya ditentukan saat register. Jangan pernah diperbarui lewat Edit Profil.
        // Abaikan nilai 'domicile' apa pun yang dikirim user agar nilai database tetap utuh.
        $request->request->remove('domicile');
        unset($validated['domicile']);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_path'] = asset('storage/' . $path);
        }

        unset($validated['avatar']);
        $user->update($validated);
        $user->refresh();
        session(['account_name' => $user->name, 'account_username' => $user->username, 'account_avatar' => $user->avatar_path]);

        return redirect()->route('profile')->with('status', 'Profil Anda berhasil diperbarui.');
    }

    /**
     * Update Password Pengguna
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        if (! session()->has('account_id') || session('account_role') !== 'customer') {
            return redirect()->route('login');
        }

        $user = User::findOrFail(session('account_id'));

        if ($user->status === 'suspended' || $user->status === 'inactive') {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED).']);
        }

        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini tidak cocok.']);
        }

        $user->update([
            'password' => $request->input('password'),
        ]);

        return redirect()->route('profile')->with('status', 'Password Anda berhasil diubah.');
    }

    /**
     * Riwayat Penyewaan Pengguna (Rental History) dengan Eager Loading Review
     */
    public function history(Request $request): View|RedirectResponse
    {
        if (! session()->has('account_id') || session('account_role') !== 'customer') {
            return redirect()->route('login')
                ->with('status', 'Silakan login untuk melihat riwayat penyewaan Anda.');
        }

        $userId = session('account_id');
        $user = User::find($userId);

        if (! $user || $user->status === 'suspended' || $user->status === 'inactive') {
            session()->forget(['account_id', 'account_name', 'account_username', 'account_role', 'account_avatar']);
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah ditangguhkan (SUSPENDED). Silakan hubungi Administrator.']);
        }

        $filter = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = Order::with(['items.product', 'payments', 'returns', 'review', 'refunds', 'latePenalty'])
            ->where('user_id', $userId);

        if ($filter === 'active') {
            $query->whereIn('status', ['active', 'paid', 'pending']);
        } elseif ($filter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($filter === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($itemQ) use ($search) {
                      $itemQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $activeCount = Order::where('user_id', $userId)
            ->whereIn('status', ['active', 'paid', 'pending'])
            ->count();

        $completedCount = Order::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        return view('home.history', compact('orders', 'activeCount', 'completedCount', 'filter', 'search'));
    }
}

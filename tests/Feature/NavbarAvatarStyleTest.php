<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarAvatarStyleTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_avatar_displays_single_username_initial_and_profile_displays_username_initial(): void
    {
        // 1. User Fajar Pratama (@fajar_peaks)
        $fajar = User::create([
            'name' => 'Fajar Pratama',
            'username' => 'fajar_peaks',
            'email' => 'fajar@summit.id',
            'password' => 'password123',
        ]);

        $resFajarHome = $this->withSession([
            'account_id' => $fajar->id,
            'account_name' => $fajar->name,
            'account_username' => '@fajar_peaks',
            'account_role' => 'customer',
        ])->get('/');

        $resFajarHome->assertStatus(200);
        $resFajarHome->assertSee('<span class="user-avatar-initial">F</span>', false);

        $resFajarProfile = $this->withSession([
            'account_id' => $fajar->id,
            'account_name' => $fajar->name,
            'account_username' => 'fajar_peaks',
            'account_role' => 'customer',
        ])->get('/profile');

        $resFajarProfile->assertStatus(200);
        $resFajarProfile->assertSee('<span class="cp-avatar-initial">F</span>', false); // Username initial in Profile
        $resFajarProfile->assertSee('<span class="user-avatar-initial">F</span>', false); // Navbar in Profile page

        // 2. User Wahyu Pratama (@wahyu)
        $wahyu = User::create([
            'name' => 'Wahyu Pratama',
            'username' => 'wahyu',
            'email' => 'wahyu@summit.id',
            'password' => 'password123',
        ]);

        $resWahyu = $this->withSession([
            'account_id' => $wahyu->id,
            'account_name' => $wahyu->name,
            'account_username' => 'wahyu',
            'account_role' => 'customer',
        ])->get('/');

        $resWahyu->assertStatus(200);
        $resWahyu->assertSee('<span class="user-avatar-initial">W</span>', false);

        // 3. User Andi Setiawan (@andi)
        $andi = User::create([
            'name' => 'Andi Setiawan',
            'username' => 'andi',
            'email' => 'andi@summit.id',
            'password' => 'password123',
        ]);

        $resAndi = $this->withSession([
            'account_id' => $andi->id,
            'account_name' => $andi->name,
            'account_username' => '@andi',
            'account_role' => 'customer',
        ])->get('/');

        $resAndi->assertStatus(200);
        $resAndi->assertSee('<span class="user-avatar-initial">A</span>', false);
    }
}

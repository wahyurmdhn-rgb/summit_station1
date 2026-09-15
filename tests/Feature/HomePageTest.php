<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_homepage(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Summit Station');
        $response->assertSee('EDISI EKSPEDISI 2026');
        $response->assertSee('Peralatan Mendaki');
        $response->assertSee('Premium');
        $response->assertSee('Apa Kata Mereka?');
    }

    public function test_navbar_routes_are_accessible(): void
    {
        $this->get('/catalog')->assertStatus(200)->assertSee('Katalog');
        $this->get('/store-location')->assertStatus(200)->assertSee('Lokasi Toko');
        $this->get('/contact-admin')->assertStatus(200)->assertSee('Hubungi Admin');

        $user = User::create([
            'name' => 'Member User',
            'email' => 'member@summit.id',
            'password' => 'password',
        ]);

        $this->withSession([
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ])->get('/history')->assertStatus(200)->assertSee('Riwayat Penyewaan');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParentConsentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        $defaults = [
            'name' => 'Consent Customer',
            'email' => 'consent@example.com',
            'username' => 'consentcustomer',
            'phone' => '081234567890',
            'domicile' => 'Jakarta',
            'date_of_birth' => '1998-01-01',
            'password' => 'password123',
            'status' => 'active',
            'role' => 'customer',
            'parent_consent_status' => 'not_required',
        ];

        return User::create(array_merge($defaults, $overrides));
    }

    private function customerSession(User $user): array
    {
        return [
            'account_id' => $user->id,
            'account_name' => $user->name,
            'account_role' => 'customer',
        ];
    }

    private function adminSession(): array
    {
        return [
            'account_id' => 1,
            'account_name' => 'Admin Summit',
            'account_role' => 'admin',
        ];
    }

    public function test_profile_shows_date_of_birth_for_user_with_dob(): void
    {
        Storage::fake('local');
        $user = $this->createUser(['date_of_birth' => '2008-08-08']);
        $dobText = $user->date_of_birth_formatted;

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Tanggal Lahir');
        $this->assertNotNull($dobText);
        $response->assertSee($dobText, false);
    }

    public function test_profile_shows_placeholder_when_dob_missing(): void
    {
        $user = $this->createUser(['date_of_birth' => null]);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Tanggal Lahir');
        $this->assertNull($user->date_of_birth_formatted);
    }

    public function test_minor_with_consent_sees_consent_section_and_pdf_button(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('parent_consents/consent-test.pdf', 'PDF-CONTENT');

        $user = $this->createUser([
            'email' => 'minorconsent@example.com',
            'date_of_birth' => '2010-05-20',
            'parent_consent_status' => 'verified',
            'parent_consent_path' => 'parent_consents/consent-test.pdf',
        ]);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Surat Persetujuan Orang Tua');
        $response->assertSee('TERVERIFIKASI');
        $response->assertSee('consent-test.pdf', false);
        $response->assertSee('Lihat PDF');
        $response->assertSee(route('file.parent-consent', $user->id), false);
    }

    public function test_adult_without_consent_does_not_see_consent_section(): void
    {
        $user = $this->createUser(['email' => 'adultnoconsent@example.com', 'date_of_birth' => '1995-01-01']);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Tanggal Lahir');
        $response->assertDontSee('cp-consent-card');
        $response->assertDontSee('data-pdf-open="');
    }

    public function test_minor_without_doc_sees_belum_diunggah_state(): void
    {
        $user = $this->createUser([
            'email' => 'minornodoc@example.com',
            'date_of_birth' => '2011-02-02',
            'parent_consent_status' => 'not_required',
        ]);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Surat Persetujuan Orang Tua');
        $response->assertSee('Belum diunggah');
        $response->assertDontSee('Lihat PDF');
    }

    public function test_rejected_consent_shows_rejection_reason(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('parent_consents/consent-lama.pdf', 'PDF-CONTENT');

        $user = $this->createUser([
            'email' => 'rejectedconsent@example.com',
            'date_of_birth' => '2010-09-09',
            'parent_consent_status' => 'rejected',
            'parent_consent_path' => 'parent_consents/consent-lama.pdf',
            'parent_consent_rejected_reason' => 'Dokumen tidak terbaca dengan jelas.',
        ]);

        $response = $this->withSession($this->customerSession($user))->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('DITOLAK');
        $response->assertSee('Alasan penolakan');
        $response->assertSee('Dokumen tidak terbaca dengan jelas.');
    }

    public function test_owner_can_open_own_consent_inline(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('parent_consents/consent-owner.pdf', 'PDF-CONTENT');

        $user = $this->createUser([
            'email' => 'ownerconsent@example.com',
            'date_of_birth' => '2010-09-09',
            'parent_consent_status' => 'submitted',
            'parent_consent_path' => 'parent_consents/consent-owner.pdf',
        ]);

        $response = $this->withSession($this->customerSession($user))
            ->get(route('file.parent-consent', $user->id));

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_user_a_cannot_open_user_b_consent(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('parent_consents/consent-user-a.pdf', 'A');
        Storage::disk('local')->put('parent_consents/consent-user-b.pdf', 'B');

        $userA = $this->createUser([
            'name' => 'User A',
            'email' => 'consenta@example.com',
            'date_of_birth' => '2010-09-09',
            'parent_consent_status' => 'submitted',
            'parent_consent_path' => 'parent_consents/consent-user-a.pdf',
        ]);
        $userB = $this->createUser([
            'name' => 'User B',
            'email' => 'consentb@example.com',
            'date_of_birth' => '2010-09-09',
            'parent_consent_status' => 'submitted',
            'parent_consent_path' => 'parent_consents/consent-user-b.pdf',
        ]);

        // Pemilik boleh membuka dokumen miliknya.
        $this->withSession($this->customerSession($userA))
            ->get(route('file.parent-consent', $userA->id))
            ->assertOk();

        // User A TIDAK boleh membuka dokumen User B meski mengganti ID di URL.
        $this->withSession($this->customerSession($userA))
            ->get(route('file.parent-consent', $userB->id))
            ->assertForbidden();

        // Admin boleh membuka dokumen semua user.
        $this->withSession($this->adminSession())
            ->get(route('file.parent-consent', $userB->id))
            ->assertOk();
    }
}
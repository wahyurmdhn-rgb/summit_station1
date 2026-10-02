<?php

namespace Tests\Unit;

use App\Support\SessionPayload;
use PHPUnit\Framework\TestCase;

/**
 * Unit test untuk parser payload session.
 *
 * Fokusnya dua: payload valid harus terbaca benar, dan payload rusak/hostile
 * tidak boleh melempar exception, tidak boleh mengembalikan object, dan tidak
 * boleh memicu object injection lewat `allowed_classes => false`.
 */
class SessionPayloadTest extends TestCase
{
    public function test_it_decodes_a_valid_serialized_array(): void
    {
        $payload = base64_encode(serialize([
            'account_id' => 7,
            'account_role' => 'admin',
            '_token' => 'abc',
        ]));

        $decoded = SessionPayload::decode($payload);

        $this->assertSame(7, $decoded['account_id']);
        $this->assertSame('admin', $decoded['account_role']);
        $this->assertSame('abc', $decoded['_token']);
    }

    public function test_it_returns_empty_array_for_corrupted_payload(): void
    {
        // Base64 valid, tapi isinya bukan hasil serialize.
        $this->assertSame([], SessionPayload::decode(base64_encode('bukan-serialized-data')));
    }

    public function test_it_returns_empty_array_for_truncated_serialized_data(): void
    {
        $serialized = serialize(['account_id' => 1, 'account_role' => 'customer']);
        $truncated = substr($serialized, 0, 12);

        $this->assertSame([], SessionPayload::decode(base64_encode($truncated)));
    }

    public function test_it_returns_empty_array_for_non_base64_input(): void
    {
        // base64_decode(strict: true) menolak karakter di luar alfabet.
        $this->assertSame([], SessionPayload::decode('*** bukan base64 ***'));
    }

    public function test_it_returns_empty_array_for_empty_or_non_string_input(): void
    {
        $this->assertSame([], SessionPayload::decode(''));
        $this->assertSame([], SessionPayload::decode(null));
        $this->assertSame([], SessionPayload::decode(123));
        $this->assertSame([], SessionPayload::decode(['array']));
        $this->assertSame([], SessionPayload::decode(true));
    }

    public function test_it_never_instantiates_objects_from_payload(): void
    {
        // Allowed_classes => false mengubah tiap object menjadi __PHP_Incomplete_Class,
        // sehingga property aslinya tidak pernah dieksekusi.
        $hostile = serialize([
            'account_role' => 'admin',
            'evil' => new \stdClass(),
        ]);

        $decoded = SessionPayload::decode(base64_encode($hostile));

        $this->assertIsArray($decoded);
        $this->assertSame('admin', $decoded['account_role']);
        $this->assertNotInstanceOf(\stdClass::class, $decoded['evil']);
    }

    public function test_it_returns_empty_array_when_serialized_value_is_not_an_array(): void
    {
        $this->assertSame([], SessionPayload::decode(base64_encode(serialize('bukan-array'))));
        $this->assertSame([], SessionPayload::decode(base64_encode(serialize(123))));
        $this->assertSame([], SessionPayload::decode(base64_encode(serialize(null))));
    }
}

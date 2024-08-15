<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class VerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public $sampleHappyPath = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [
               "name" => "Marty McFly",
               "email" => "marty.mcfly@gmail.com"
           ],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    public $unhappyMissingRecipientNamePath = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [
               "name" => "",
               "email" => "marty.mcfly@gmail.com"
           ],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    public $unhappyMissingRecipientEmailPath = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [
                "name" => "",
                "email" => ""
           ],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    public $missingRecipient = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    public $invalidIssuer = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [
               "name" => "Marty McFly",
               "email" => "marty.mcfly@gmail.com"
           ],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    public $invalidSignature = [
        'data' => [
           "id" => "63c79bd9303530645d1cca00",
           "name" => "Certificate of Completion",
           "recipient" => [
               "name" => "MarMar Mcfly",
               "email" => "marty.mcfly@gmail.com"
           ],
           "issuer" => [
               "name" => "Accredify",
               "identityProof" => [
                   "type" => "DNS-DID",
                   "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                   "location" => "ropstore.accredify.io"
               ]
           ],
           "issued" => "2022-12-23T00:00:00+08:00",
        ],
        'signature' => [
           "type" => "SHA3MerkleProof",
           "targetHash" => "2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"
        ],
    ];

    /**
     * Happy path which has the correct JSON and getting the verified result.
     */
    public function test_happy_path()
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->sampleHappyPath);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'verified',
            ]
        ]);
    }

    /**
     * Unhappy path which has the missing name JSON and getting the verified result.
     */
    public function test_empty_recipient_name()
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->unhappyMissingRecipientNamePath);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'invalid_recipient',
            ]
        ]);
    }

    /**
     * Unhappy path which has the missing name JSON and getting the verified result.
     */
    public function test_empty_recipient_email()
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->unhappyMissingRecipientEmailPath);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'invalid_recipient',
            ]
        ]);
    }

    /**
     * Unhappy path which has the missing recipient.
     */
    public function test_missing_recipient()
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->unhappyMissingRecipientEmailPath);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'invalid_recipient',
            ]
        ]);
    }

    /**
     * Unhappy path which has the invalid issuer.
     */
    public function test_invalid_issuer() 
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->invalidIssuer);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'invalid_issuer',
            ]
        ]);
    }

    /**
     * Unhappy path which has the invalid signature.
     */
    public function test_invalid_signature() 
    {
        $user = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('authToken')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                         ->postJson('/api/verify', $this->invalidSignature);

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                'issuer',
                'result',
            ]
        ]);

        $response->assertJson([
            'data' => [
                'issuer' => 'Accredify',
                'result' => 'invalid_signature',
            ]
        ]);
    }
}

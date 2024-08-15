<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Result;

class VerificationController extends Controller
{
  /**
   * POST api/verify
   *
   * This endpoint allows user to pass in the JSON and verify its authenticity.
   *
   * @bodyParam data object required The data object containing the information to be verified. Example: {"data":{"id":"63c79bd9303530645d1cca00","name":"Certificate of Completion","recipient":{"name":"Marty McFly","email":"marty.mcfly@gmail.com"},"issuer":{"name":"Accredify","identityProof":{"type":"DNS-DID","key":"did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller","location":"ropstore.accredify.io"}},"issued":"2022-12-23T00:00:00+08:00"},"signature":{"type":"SHA3MerkleProof","targetHash":"2e50ae786cb02240bcec1484a92c722ce0bd2e4cdb3ae28bbb12ee3870573da5"}}
   *
   * @response 200 {
   *  "issuer": "Accredify",
   *  "result": "verified"
   * }
   *
   * @response 401 {
   *  "message": "Invalid credentials."
   * }
   */
  public function verify(Request $request)
  {
      $data = $request->json()->all();

      $recipientRules = [
        'data.recipient.name' => 'required|string|min:1',
        'data.recipient.email' => 'required|email|min:1',
      ];
      
      $validatorRecipients = Validator::make($data, $recipientRules);
      if($validatorRecipients->fails()) {
        return response()->json([
          'data' => [
            'issuer' => $data['data']['issuer']['name'],
            'result' => 'invalid_recipient'
          ]
        ]);
      }
      $isValidRecipient = true;

      $issuerRules = [
        'data.id' => 'required|string|min:1',
        'data.name' => 'required|string|min:1',
        'data.issuer.name' => 'required|string',
        'data.issuer.identityProof.type' => 'required|string|min:1',
        'data.issuer.identityProof.key' => 'required|string|min:1',
        'data.issuer.identityProof.location' => 'required|string|min:1',
        'data.issued' => 'required|string|min:1',
      ];
      $validatorIssuers = Validator::make($data, $issuerRules);
      if($validatorIssuers->fails()) {
        dd('here');
        return response()->json([
          'data' => [
            'issuer' => $data['data']['issuer']['name'],
            'result' => 'invalid_issuer'
          ]
        ]);
      }
      $isValidIssuer = true;

      $isValidSignature = false;

      $isValidIssuer = $this->checkIssuerWithDNS($data);
        
      $targetHash = $this->concatenateAndHashData($data);

      $isValidSignature = $data['signature']['targetHash'] === $targetHash;

      if ($isValidIssuer && $isValidRecipient && $isValidSignature) {
        $result = 'verified';
      } elseif (!$isValidIssuer) {
        $result = 'invalid_issuer';
      } elseif (!$isValidRecipient) {
        $result = 'invalid_recipient';
      } elseif (!$isValidSignature) {
        $result = 'invalid_signature';
      }

      Result::create([
        'filetype' => 'json',
        'result' => $result
      ]);

      return response()->json([
        'data' => [
          'issuer' => $data['data']['issuer']['name'],
          'result' => $result
        ]
      ]);
  }

  private function checkSignature(Response $responseIssuer, $data)
  {
    $bodyResponse = json_decode($responseIssuer->body(), true);
    $bodyResponseIssuerAnswers = $bodyResponse['Answer'];
    $found = false;
    $target = $data['data']['issuer']['identityProof']['key'];

    foreach ($bodyResponseIssuerAnswers as $answer) {
      if(strpos($answer['data'], $target) !== false) {
        $found = true;
        break;
      }
    }
    return $found;
  }

  private function checkIssuerWithDNS($data) 
  {
    $result = false;
    $responseIssuer = Http::withHeaders([])->get('https://dns.google/resolve?name='.$data['data']['issuer']['identityProof']['location'].'&type=TXT');
    if ($responseIssuer->successful()) {
      $found = $this->checkSignature($responseIssuer, $data);
      if($found) {
        $result = true;
      } else {
        $result = false;
      }
    } else {
      $result = false;
    }
    return $result;
  }

  private function concatenateAndHashData($data) 
  {
    $hashes = [];
    $tmpData = [
      'id' => $data['data']['id'],
      'name' => $data['data']['name'],
      'recipient.name' => $data['data']['recipient']['name'],
      'recipient.email' => $data['data']['recipient']['email'],
      'issuer.name' => $data['data']['issuer']['name'],
      'issuer.identityProof.type' => $data['data']['issuer']['identityProof']['type'],
      'issuer.identityProof.key' => $data['data']['issuer']['identityProof']['key'],
      'issuer.identityProof.location' => $data['data']['issuer']['identityProof']['location'],
      'issued' => $data['data']['issued']
    ];
    foreach ($tmpData as $key => $value) {
      $hashes[] = hash('sha256', $key . $value);
    }
    sort($hashes);
    $concatenatedHashes = implode('', $hashes);
    $targetHash = hash('sha256', $concatenatedHashes);
    return $targetHash;
  }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\Result;

class VerificationController extends Controller
{
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

<?php
// App/Services/ESignatureService.php

namespace App\Services;

use App\Models\DealDocument;
use App\Models\SignatureRequest;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class ESignatureService
{
    public function requestSignature(DealDocument $document, array $signers)
    {
        $signatureRequest = SignatureRequest::create([
            'document_id' => $document->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7)
        ]);

        foreach ($signers as $signer) {
            $signature = Signature::create([
                'signature_request_id' => $signatureRequest->id,
                'user_id' => $signer['id'],
                'role' => $signer['role'],
                'status' => 'pending',
                'signing_url' => $this->generateSigningUrl($document, $signer),
                'security_token' => Str::random(64)
            ]);

            // Send email notification
            $this->sendSignatureRequestEmail($signature, $document);
        }

        return $signatureRequest;
    }

    public function processSignature($signatureId, $ipAddress = null)
    {
        $signature = Signature::findOrFail($signatureId);

        $signature->update([
            'status' => 'signed',
            'signed_at' => now(),
            'ip_address' => $ipAddress
        ]);

        // Check if all signatures are complete
        $this->checkSignatureRequestCompletion($signature->signatureRequest);

        return $signature;
    }

    private function generateSigningUrl(DealDocument $document, $signer)
    {
        return url("/sign/{$document->id}/{$signer['id']}/" . Str::random(32));
    }

    private function sendSignatureRequestEmail(Signature $signature, DealDocument $document)
    {
        $user = User::find($signature->user_id);

        Mail::send('emails.signature-request', [
            'signature' => $signature,
            'document' => $document,
            'user' => $user
        ], function ($message) use ($user, $document) {
            $message->to($user->email)
                ->subject("Signature Required: {$document->file_name}");
        });
    }

    private function checkSignatureRequestCompletion(SignatureRequest $signatureRequest)
    {
        $pendingSignatures = $signatureRequest->signatures()
            ->where('status', 'pending')
            ->count();

        if ($pendingSignatures === 0) {
            $signatureRequest->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Update document status
            $signatureRequest->document->update([
                'signed_at' => now()
            ]);
        }
    }

    public function getSignersForDocument($deal, $sharedWith)
    {
        $signers = [];

        if (in_array('buyer', $sharedWith)) {
            $signers[] = [
                'id' => $deal->buyer_id,
                'role' => 'buyer',
                'email' => $deal->buyer->email
            ];
        }

        if (in_array('seller', $sharedWith)) {
            $signers[] = [
                'id' => $deal->seller_id,
                'role' => 'seller',
                'email' => $deal->seller->email
            ];
        }

        return $signers;
    }
}

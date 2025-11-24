<?php

namespace App\Http\Controllers;

use App\Models\Deal;
use App\Models\DealDocument;
use App\Services\DealProgressService;
use App\Services\ESignatureService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request;

class DealController extends Controller
{
    public function __construct(private DealProgressService $progressService, protected ESignatureService $eSignatureService) {}

    public function getAgentDeals()
    {
        $deals = Deal::with(['property', 'buyer', 'seller'])
            ->where('agent_id', auth()->id())
            ->whereIn('status', ['under_contract', 'pending_sale', 'closing'])
            ->get()
            ->map(function ($deal) {
                return [
                    'id' => $deal->id,
                    'address' => $deal->property->address ?? 'N/A',
                    'buyer' => $deal->buyer->name ?? 'Unknown Buyer',
                    'seller' => $deal->seller->name ?? 'Unknown Seller',
                    'status' => $deal->current_step,
                    'price' => '$' . number_format($deal->final_price),
                    'acceptedDate' => $deal->accepted_date->format('Y-m-d'),
                    'nextStep' => $this->progressService->getNextStepDescription($deal->current_step),
                    'deadline' => $this->progressService->getStepDeadline($deal->current_step, $deal->accepted_date),
                    'priority' => $this->progressService->getPriority($deal->current_step, $deal->accepted_date),
                    'progress' => $deal->progress_percentage
                ];
            });

        return response()->json($deals);
    }

    public function store_document(Request $request,)
    {

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'document_type' => 'required|string',
            'description' => 'nullable|string',
            'shared_with' => 'required|array',
            'requires_signature' => 'required|in:true,false,1,0,true,false',
            'deal_id' => 'required|exists:deals,id'
        ]);
        $deal_data = Deal::findOrFail($request->deal_id);
        // Map document_type to category
        $category = $this->progressService->getCategoryFromDocumentType($request->document_type);

        $file = $request->file('file');
        $timestamp = now()->format('Ymd_His');

        $conventionName = sprintf(
            '%s_%s.%s',
            $request->document_type,
            $timestamp,
            $file->getClientOriginalExtension()
        );

        // Upload file
        // $fileUrl = Storage::disk('s3')->putFileAs(
        //     "deals/{$deal->id}",
        //     $file,
        //     $conventionName
        // );
        $path = Storage::disk('public')->putFileAs(
            "deals/{$deal_data->id}",
            $file,
            $conventionName
        );

        $document = DealDocument::create([
            'deal_id' => $deal_data->id,
            'property_offer_id' => $deal_data->offer_id,
            'uploaded_by' => auth()->id(),
            'document_name' => $conventionName,
            'original_name' => $file->getClientOriginalName(),
            'file_url' => asset(Storage::url($path)),
            'file_size' => $file->getSize(),
            'file_type' => $file->getClientOriginalExtension(),
            'document_type' => $request->document_type,
            'category' => $category,
            'description' => $request->description,
            'shared_with' => $request->shared_with,
            'requires_signature' => $request->boolean('requires_signature'),
            'signed_at' => null,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $document
        ], 201);
    }

    public function get_document_details(Request $request, $deal_id)
    {
        if (!in_array($request->user()->role_id, [3])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }
        $documents = DealDocument::where('deal_id', $deal_id)->get();
        return response()->json([
            'message' => 'Document uploaded successfully',
            'document' => $documents
        ], 201);
    }    

    public function checkAndUpdateStepProgress($deal_id, $stepKey)
    {
        $requiredDocs = $this->progressService->getRequiredDocumentsForStep($stepKey);

        $uploadedDocs = DealDocument::where('deal_id', $deal_id)
            ->whereIn('document_type', $requiredDocs)
            ->get();
        
        $allUploaded = collect($requiredDocs)->every(function ($docType) use ($uploadedDocs) {
            return $uploadedDocs->where('document_type', $docType)->isNotEmpty();
        });

        if ($allUploaded) {            
            $this->markStepComplete($deal_id, $stepKey);
        }
    }

    public function markStepComplete($dealId, $stepKey)
    {
        $deal = Deal::findOrFail($dealId);

        $stepProgress = [
            'contract_generation' => 20,
            'earnest_money' => 40,
            'inspection' => 60,
            'mortgage_processing' => 80,
            'closing_preparation' => 90,
            'closed' => 100
        ];

        $nextStep = $this->progressService->getNextStep($stepKey);
        $progress = $stepProgress[$nextStep] ?? $deal->progress_percentage;

        $deal->update([
            'current_step' => $nextStep,
            'progress_percentage' => $progress,
            'status' => $nextStep === 'closed' ? 'closed' : $deal->status
        ]);
        
        $this->sendStepCompletionNotifications($deal, $stepKey);

        return response()->json([
            'deal' => $deal,
            'message' => 'Step marked as complete'
        ]);
    }  

    private function sendStepCompletionNotifications($deal, $stepKey)
    {
        // Send email notifications to relevant parties
        // Implement your notification logic here
    }
}

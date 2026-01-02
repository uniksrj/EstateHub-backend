<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Deal;
use App\Models\DealDocument;
use App\Services\ActivityService;
use App\Services\DealProgressService;
use App\Services\ESignatureService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function __construct(private DealProgressService $progressService, protected ESignatureService $eSignatureService, protected ActivityService $activity_service) {}

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
                    'deadline' => $this->progressService->getStepDeadline($deal->current_step, $deal->accepted_date, $deal->deadline_extensions ?? 0, $deal->last_extension_date ?? null),
                    'priority' => $this->progressService->getPriority($deal->current_step, $deal->accepted_date),
                    'progress' => $deal->progress_percentage,
                    'deadline_status' =>  $this->progressService->getDeadlineStatus(
                        $deal->current_step,
                        $deal->accepted_date,
                        $deal->deadline_extensions ?? 0,
                        $deal->last_extension_date ? Carbon::parse($deal->last_extension_date) : null
                    ),
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

        ActivityService::logDocumentUpload($deal_data, $document, auth()->user());

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
            return $this->markStepComplete($deal_id, $stepKey);
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
        $progress = $stepProgress[$nextStep];

        $deal->update([
            'current_step' => $nextStep,
            'progress_percentage' => $progress,
            'status' => $nextStep === 'closed' ? 'closed' : $deal->status
        ]);
        ActivityService::logStepCompleted($deal, $stepKey, auth()->user());
        $this->sendStepCompletionNotifications($deal, $stepKey);

        return response()->json([
            'deal' => $deal,
            'message' => 'Step marked as complete',
            'percentage' => $progress
        ]);
    }

    private function sendStepCompletionNotifications($deal, $stepKey)
    {
        // Send email notifications to relevant parties
    }

    public function get_activity_details(Request $request)
    {
        $query = Activity::with(['user', 'deal', 'document'])
            ->orderBy('created_at', 'desc');

        if ($request->has('deal_id')) {
            $query->where('deal_id', $request->deal_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $activities = $query->limit(100)->get();

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return $this->activity_service->formatActivity($activity);
            })
        ]);
    }

    public function forDeal(Deal $deal)
    {
        $activities = Activity::with(['user', 'document'])
            ->where('deal_id', $deal->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'activities' => $activities->map(function ($activity) {
                return $this->activity_service->formatActivity($activity);
            })
        ]);
    }

    public function update_deal(Request $request, $deal_id)
    {
        if (!in_array($request->user()->role_id, [3])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $request->validate([
            'progress' => 'sometimes|integer|min:0|max:100',
            'status' => 'sometimes|string|max:255',
            'nextStep' => 'sometimes|string|max:255',
            'priority' => 'sometimes|in:low,medium,high',
        ]);

        $deal = Deal::findOrFail($deal_id);
        if ($request->has('progress')) {
            $deal->progress_percentage = $request->progress;
        }
        if ($request->has('status')) {
            // $deal->status = $request->status;
        }
        if ($request->has('nextStep')) {
            $deal->current_step = $request->nextStep;
        }
        $deal->save();
        return response()->json([
            'message' => 'Deal updated successfully',
            'deal' => $deal
        ], 200);
    }

    public function add_deadline_extension(Request $request, $deal_id)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30',
            'reason' => 'required|string|min:5'
        ]);

        try {
            $deal = Deal::findOrFail($deal_id);

            $currentDeadline = $this->progressService->getStepDeadline(
                $deal->current_step,
                $deal->accepted_date,
                $deal->deadline_extensions ?? 0
            );
            $today = Carbon::today();
            if ($today->greaterThan(Carbon::parse($currentDeadline))) {
                $newDeadline = $today->addDays($request->days);
            } else {
                $newDeadline = Carbon::parse($currentDeadline)->addDays($request->days);
            }

            $deal->deadline_extensions = ($deal->deadline_extensions ?? 0) + 1;
            $deal->last_extension_date = $newDeadline;
            $deal->extension_reason = $request->reason;
            $deal->deadline_status = 'extended';
            $deal->save();

            $this->activity_service->store_deadExtension_record($deal->id, auth()->id(), $request->days, $request->reason, 'deadline', $currentDeadline, $newDeadline->format('Y-m-d'));
            ActivityService::logDeadlineExtension($deal, $request->days, $request->reason, auth()->user());


            return response()->json([
                "success" => true,
                "message" => "Added {$request->days}-day extension",
                "new_deadline" => $deal->deadline,
                "extended_until" => now()->addDays($request->days)->format('Y-m-d')
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                "success" => false,
                "message" => "Failed to add extension: " . $th->getMessage()
            ], 500);
        }
    }

    public function update_earnest_deal(Request $request, $deal_id)
    {
        if (!in_array($request->user()->role_id, [3])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $deal = Deal::findOrFail($deal_id);

        $request->validate([
            'earnest_amount' => 'required|numeric|min:0',
            'earnest_payment_method' => 'required|string|max:255',
            'earnest_status' => 'required|in:pending,received',
            'earnest_due_date' => 'required|date',
            'earnest_received_date' => 'nullable|date|required_if:earnest_status,received'
        ]);

        $deal->earnest_money_deposit = $request->earnest_amount;
        $deal->earnest_payment_method = $request->earnest_payment_method;
        $deal->earnest_status = $request->earnest_status;
        $deal->earnest_due_date = $request->earnest_due_date;
        $deal->earnest_received_date = $request->earnest_status === 'received' ? $request->earnest_received_date : null;

        $deal->save();

        return response()->json([
            'message' => 'Earnest money details updated successfully',
            'deal' => $deal
        ], 200);
    }

    public function update_documents_details(Request $request, $deal_id)
    {
        if (!in_array($request->user()->role_id, [3])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $request->validate([
            'document_types' => 'required|array',
            'notes' => 'nullable|array'
        ]);
        $deal = Deal::findOrFail($deal_id);
        $dealDocuments = DealDocument::where('deal_id', $deal->id)->get();

        $existingDocTypes = $dealDocuments->pluck('document_type')->toArray();
        $documentTypes = $request->document_types;
        $notes = $request->notes;

        $documentTypes = array_values(
            array_diff($request->document_types, $existingDocTypes)
        );

        foreach ($documentTypes as $docType) {
            $stepKey = $this->progressService->getStepFromDocumentName($docType);
            $noteTxt = collect($notes)->firstWhere('stage', $stepKey)['text'] ?? '';
            DealDocument::create([
                'deal_id' => $deal->id,
                'property_offer_id' => $deal->offer_id,
                'uploaded_by' => auth()->id(),
                'document_type' => $docType,
                'category' => $this->progressService->getCategoryFromDocumentType($docType),
                'notes' => $noteTxt,
                'file_url' => '',
                'file_size' => 0,
                'file_type' => '',
                'document_name' => '',
                'shared_with' => ['seller', 'buyer'],
                'status' => 'marked_received',
                'marked_received_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Document details updated successfully'
        ], 200);
    }
}

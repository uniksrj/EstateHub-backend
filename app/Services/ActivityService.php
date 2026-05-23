<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Deadline_extensions;
use App\Models\Deal;
use App\Models\Property;
use App\Models\User;

class ActivityService
{
    public static function log(
        Deal $deal,
        string $type,
        string $action,
        string $item,
        string $step,
        string $status = 'completed',
        User $user = null,
        $document = null,
        array $metadata = []
    ): Activity {
        return Activity::create([
            'deal_id' => $deal->id,
            'user_id' => $user ? $user->id : auth()->id(),
            'document_id' => $document ? $document->id : null,
            'type' => $type,
            'action' => $action,
            'item' => $item,
            'step' => $step,
            'status' => $status,
            'message' => self::generateMessage($type, $action, $item, $user),
            'metadata' => $metadata
        ]);
    }

    private static function generateMessage($type, $action, $item, $user = null)
    {
        $userName = $user ? $user->name : 'System';

        $messages = [
            'document_upload' => "{$item} uploaded by {$userName}",
            'document_signed' => "{$item} signed by {$userName}",
            'document_request' => "{$item} requested by {$userName}",
            'step_completed' => "{$item} step completed",
            'step_started' => "{$item} step initiated",
            'payment_received' => "{$item} received",
            'payment_due' => "{$item} due",
            'deadline_approaching' => "{$item} deadline approaching",
        ];

        return $messages[$type] ?? "{$action} {$item}";
    }

    public static function logDocumentUpload(Deal $deal, $document, $user = null)
    {
        return self::log(
            $deal,
            'document_upload',
            'uploaded',
            $document->document_name,
            $deal->current_step,
            'completed',
            $user,
            $document,
            ['document_type' => $document->document_type]
        );
    }

    public static function logDocumentSigned(Deal $deal, $document, $user)
    {
        return self::log(
            $deal,
            'document_signed',
            'signed',
            $document->original_name,
            $deal->current_step,
            'completed',
            $user,
            $document
        );
    }

    public static function logStepCompleted(Deal $deal, $stepName, $user = null)
    {
        return self::log(
            $deal,
            'step_completed',
            'completed',
            $stepName,
            $stepName,
            'completed',
            $user
        );
    }

    public static function logStepStarted(Deal $deal, $stepName, $user = null)
    {
        return self::log(
            $deal,
            'step_started',
            'started',
            $stepName,
            $stepName,
            'active',
            $user
        );
    }

    public static function logPayment(Deal $deal, $paymentType, $amount, $user = null)
    {
        return self::log(
            $deal,
            'payment_received',
            'received',
            "{$paymentType} - ₹" . number_format($amount),
            $deal->current_step,
            'completed',
            $user,
            null,
            ['amount' => $amount, 'type' => $paymentType]
        );
    }

    public static function logDeadline(Deal $deal, $deadlineType, $daysLeft)
    {
        return self::log(
            $deal,
            'deadline_approaching',
            'reminder',
            $deadlineType,
            $deal->current_step,
            $daysLeft <= 1 ? 'urgent' : 'pending',
            null,
            null,
            ['days_remaining' => $daysLeft]
        );
    }

    public function formatActivity(Activity $activity)
    {        
        $typeConfig = $this->getActivityTypeConfig($activity->type);
        $property = Property::findOrFail($activity->deal->property_id);
        return [
            'id' => $activity->id,
            'type' => $activity->type,
            'message' => $activity->message,
            'time' => $activity->time_ago,
            'status' => $activity->status,
            'icon' => $typeConfig['icon'],
            'color' => $typeConfig['color'],
            'property' => $property->address ?? 'Unknown Property',
            'user' => $activity->user->name ?? 'System',
            'action' => $activity->action,
            'item' => $activity->item,
            'step' => $activity->step,
            'metadata' => $activity->metadata
        ];
    }

    private function getActivityTypeConfig($type)
    {
        $configs = [
            'document_upload' => ['icon' => 'Upload', 'color' => 'text-green-500'],
            'document_signed' => ['icon' => 'PenTool', 'color' => 'text-blue-500'],
            'document_request' => ['icon' => 'Download', 'color' => 'text-amber-500'],
            'step_completed' => ['icon' => 'CheckCircle2', 'color' => 'text-green-500'],
            'step_started' => ['icon' => 'Clock', 'color' => 'text-blue-500'],
            'payment_received' => ['icon' => 'DollarSign', 'color' => 'text-green-500'],
            'payment_due' => ['icon' => 'AlertCircle', 'color' => 'text-red-500'],
            'deadline_approaching' => ['icon' => 'AlertCircle', 'color' => 'text-amber-500'],
        ];

        return $configs[$type] ?? ['icon' => 'CheckCircle2', 'color' => 'text-gray-500'];
    }

    public static function logOfferSubmitted(Deal $deal, User $submittedBy, $offerAmount, $offerType = 'initial')
    {
        return self::log(
            $deal,
            'offer_submitted',
            'submitted',
            "{$offerType} offer",
            'offer_submission',
            'completed',
            $submittedBy,
            null,
            [
                'offer_amount' => $offerAmount,
                'offer_type' => $offerType,
                'property_address' => $deal->property_address
            ]
        );
    }

    public static function logOfferAccepted(Deal $deal, User $acceptedBy, $offerType = 'initial', $acceptedAmount = null)
    {
        $item = $offerType === 'counter' ? 'Counter Offer' : 'Purchase Offer';

        return self::log(
            $deal,
            $offerType === 'counter' ? 'counter_offer_accepted' : 'offer_accepted',
            'accepted',
            $item,
            'offer_acceptance',
            'completed',
            $acceptedBy,
            null,
            [
                'accepted_amount' => $acceptedAmount ?? $deal->offer_price,
                'offer_type' => $offerType,
                'property_address' => $deal->property_address
            ]
        );
    }

    public static function logOfferRejected(Deal $deal, User $rejectedBy, $offerType = 'initial', $reason = null)
    {
        return self::log(
            $deal,
            'offer_rejected',
            'rejected',
            $offerType === 'counter' ? 'Counter Offer' : 'Purchase Offer',
            'offer_response',
            'completed',
            $rejectedBy,
            null,
            [
                'rejection_reason' => $reason,
                'offer_type' => $offerType
            ]
        );
    }

    public static function logCounterOffer(Deal $deal, User $submittedBy, $counterAmount, $previousAmount)
    {
        return self::log(
            $deal,
            'counter_offer_submitted',
            'submitted',
            'Counter Offer',
            'offer_negotiation',
            'completed',
            $submittedBy,
            null,
            [
                'counter_amount' => $counterAmount,
                'previous_amount' => $previousAmount,
                'difference' => $counterAmount - $previousAmount
            ]
        );
    }

    public static function logOfferWithdrawn(Deal $deal, User $withdrawnBy, $reason = null)
    {
        return self::log(
            $deal,
            'offer_withdrawn',
            'withdrawn',
            'Purchase Offer',
            'offer_management',
            'completed',
            $withdrawnBy,
            null,
            ['withdrawal_reason' => $reason]
        );
    }

    public static function logOfferExpired(Deal $deal)
    {
        return self::log(
            $deal,
            'offer_expired',
            'expired',
            'Purchase Offer',
            'offer_management',
            'completed',
            null, // system generated
            null,
            ['expired_at' => now()->toISOString()]
        );
    }

    public static function logDeadlineExtension(Deal $deal, $extensionDays, $reason = null, $user = null)
    {
        return self::log(
            $deal,
            'deadline_extended',
            'extended',
            'Deadline Extension',
            $deal->current_step,
            'completed',
            $user,
            null,
            [
                'extension_days' => $extensionDays,
                'reason' => $reason
            ]
        );
    }

    public static function logDeadlineMissed(Deal $deal, $deadlineType, $user = null)
    {
        return self::log(
            $deal,
            'deadline_missed',
            'missed',
            "{$deadlineType} Deadline Missed",
            $deal->current_step,
            'completed',
            $user,
            null,
            ['deadline_type' => $deadlineType]
        );
    }

    public function store_deadExtension_record($deal_id, $extended_by, $extension_days, $reason, $extension_type, $old_deadline = null, $new_deadline = null)
    {

        return Deadline_extensions::create([
            'deal_id' => $deal_id,
            'extended_by' => $extended_by,
            'reason' => $reason,
            'extension_type' => $extension_type,
            'extension_days' => $extension_days,
            'old_deadline' => $old_deadline,
            'new_deadline' => $new_deadline,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}

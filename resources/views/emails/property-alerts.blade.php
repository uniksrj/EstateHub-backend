<div>
   @component('mail::message')
# {{ $count }} New Properties Match Your Criteria!

Hello! We found {{ $count }} new properties that match your search criteria.

@component('mail::button', ['url' => url('/buyer/alerts/' . $alert->id)])
View Matching Properties
@endcomponent

## Matching Properties Summary:

@foreach($properties->take(5) as $property)
### {{ $property->title }}
- **Price:** ${{ number_format($property->price) }}
- **Bedrooms:** {{ $property->bedrooms }}
- **Bathrooms:** {{ $property->bathrooms }}
- **Type:** {{ $property->property_type }}
- **Location:** {{ $property->city }}, {{ $property->state }}

---
@endforeach

@if($properties->count() > 5)
*... and {{ $properties->count() - 5 }} more properties*
@endif

@component('mail::button', ['url' => url('/buyer/alerts/' . $alert->id)])
View All {{ $count }} Properties
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
</div>

<?php

namespace Webkul\RestApi\Http\Resources\V1\Contact;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\RestApi\Http\Resources\V1\Concerns\InteractsWithCustomAttributes;
use Webkul\RestApi\Http\Resources\V1\Setting\UserResource;

class PersonResource extends JsonResource
{
    use InteractsWithCustomAttributes;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge($this->customAttributes(), [
            'id'              => $this->id,
            'name'            => $this->name,
            'emails'          => $this->emails,
            'contact_numbers' => $this->contact_numbers,
            'organization'    => $this->when($this->organization, new OrganizationResource($this->organization)),
            'job_title'       => $this->job_title,
            'sales_owner'     => $this->when($this->user, new UserResource($this->user)),
            'unsubscribe_url' => $this->when(class_exists(\Webkul\Contact\Services\UnsubscribeToken::class), fn () => \Webkul\Contact\Services\UnsubscribeToken::url($this->id)),
            // Always present, as the option label (or null), so senders can check it before emailing.
            'do_not_contact'  => $this->doNotContact(),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ]);
    }

    /**
     * The "Do not contact" select as its option label, or null when unset. The raw
     * EAV value is an option id, which no sender can interpret; the label is what
     * the CRM shows and what update_person accepts.
     */
    protected function doNotContact(): ?string
    {
        static $attribute = false;

        if ($attribute === false) {
            $attribute = app(\Webkul\Attribute\Repositories\AttributeRepository::class)
                ->findOneWhere(['code' => 'do_not_contact', 'entity_type' => 'persons']);
        }

        if (! $attribute || ! method_exists($this->resource, 'getCustomAttributeValue')) {
            return null;
        }

        $value = $this->resource->getCustomAttributeValue($attribute);

        if ($value === null || $value === '') {
            return null;
        }

        return $attribute->options()->where('id', $value)->value('name');
    }
}

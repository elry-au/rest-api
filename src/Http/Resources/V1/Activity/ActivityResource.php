<?php

namespace Webkul\RestApi\Http\Resources\V1\Activity;

use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Activity\Contracts\Activity as ActivityContract;
use Webkul\Admin\Http\Resources\UserResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /**
         * Lead activity listings mix in emails as plain objects, which have no
         * lead relation or participants to read.
         */
        $isActivity = $this->resource instanceof ActivityContract;

        return [
            'id'            => $this->id,
            'parent_id'     => $this->parent_id ?? null,
            'title'         => $this->title,
            'type'          => $this->type,
            'comment'       => $this->comment,
            'additional'    => is_array($this->resource->additional) ? $this->resource->additional : json_decode($this->resource->additional, true),
            'schedule_from' => $this->schedule_from,
            'schedule_to'   => $this->schedule_to,
            'is_done'       => $this->is_done,
            'lead_id'       => $isActivity ? $this->leadId() : null,
            'user'          => new UserResource($this->user),
            'assignee'      => $isActivity && ($assignee = $this->resource->assignee)
                ? ['id' => $assignee->id, 'name' => $assignee->name, 'email' => $assignee->email]
                : null,
            'files'         => ActivityFileResource::collection($this->files),
            'participants'  => ActivityParticipantResource::collection($this->participants),
            'location'      => $this->location,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }

    /**
     * The activity's lead. Listings select it up front as `lead_id`; otherwise it
     * is read from the pivot. An activity linked to several leads reports the
     * lowest lead id, so the answer is stable.
     */
    protected function leadId(): ?int
    {
        $leadId = array_key_exists('lead_id', $this->resource->getAttributes())
            ? $this->resource->getAttribute('lead_id')
            : $this->resource->leads()->min('leads.id');

        return $leadId === null ? null : (int) $leadId;
    }
}

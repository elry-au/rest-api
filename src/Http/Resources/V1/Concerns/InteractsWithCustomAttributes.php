<?php

namespace Webkul\RestApi\Http\Resources\V1\Concerns;

trait InteractsWithCustomAttributes
{
    /**
     * Collect the entity's user-defined (custom) EAV attribute values as a
     * `code => value` map, so REST resources can expose custom fields the same
     * way the Krayin panel does.
     *
     * Only attributes flagged `is_user_defined` are returned: system attributes
     * are already exposed as the resource's fixed keys, and skipping them means
     * a custom code can never collide with (or overwrite) an existing response
     * key. Select fields come back as their option label ("Bid", not 47) and
     * user-lookup selects as the user's name, matching what writes accept; a
     * value with no matching option is returned raw so nothing is hidden. Other
     * types are the raw EAV values.
     *
     * Returns an empty array for models that do not use Krayin's CustomAttribute
     * trait, keeping the calling resource safe for plain models and unit tests.
     *
     * @return array<string, mixed>
     */
    protected function customAttributes(): array
    {
        $model = $this->resource;

        if (! is_object($model) || ! method_exists($model, 'getCustomAttributes')) {
            return [];
        }

        $values = [];

        foreach ($model->getCustomAttributes() as $attribute) {
            if (! $attribute->is_user_defined) {
                continue;
            }

            $values[$attribute->code] = $this->customAttributeLabel(
                $attribute,
                $model->getCustomAttributeValue($attribute)
            );
        }

        return $values;
    }

    /**
     * The display label for a select value: the option name, or for a lookup
     * select the looked-up record's name. Option lists are cached per request.
     */
    protected function customAttributeLabel($attribute, mixed $value): mixed
    {
        static $options = [];

        if ($attribute->type !== 'select' || $value === null || $value === '') {
            return $value;
        }

        if ($attribute->lookup_type) {
            $entity = app(\Webkul\Attribute\Repositories\AttributeRepository::class)
                ->getLookUpEntity($attribute->lookup_type, $value);

            return $entity?->name ?? $value;
        }

        // Refresh once on a miss, so an option added since the cache filled is still found.
        if (! isset($options[$attribute->id][$value])) {
            $options[$attribute->id] = $attribute->options()->pluck('name', 'id')->all();
        }

        return $options[$attribute->id][$value] ?? $value;
    }
}

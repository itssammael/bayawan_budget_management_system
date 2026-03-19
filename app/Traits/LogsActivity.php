<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    protected static function booted()
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    public function logActivity(string $event)
    {
        $description = $this->getActivityDescription($event);
        $properties = $this->getActivityProperties($event);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'log_name' => strtolower(class_basename($this)),
            'description' => $description,
            'subject_type' => get_class($this),
            'subject_id' => $this->id,
            'event' => $event,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    protected function getActivityDescription(string $event): string
    {
        $user = auth()->user() ? auth()->user()->name : 'System';
        $modelName = class_basename($this);
        
        return "{$user} {$event} a {$modelName}";
    }

    protected function getActivityProperties(string $event): array
    {
        $properties = [
            'attributes' => $this->getAttributes(),
        ];

        if ($event === 'updated') {
            $properties['old'] = array_intersect_key($this->getOriginal(), $this->getDirty());
            $properties['attributes'] = $this->getDirty();
        }

        // Remove sensitive fields
        $sensitive = ['password', 'remember_token', 'two_factor_recovery_codes', 'two_factor_secret'];
        foreach ($sensitive as $field) {
            unset($properties['attributes'][$field]);
            if (isset($properties['old'][$field])) {
                unset($properties['old'][$field]);
            }
        }

        return $properties;
    }
}

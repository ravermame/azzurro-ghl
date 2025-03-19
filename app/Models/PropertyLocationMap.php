<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyLocationMap extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['property_id', 'location_id', 'pipeline_id', 'pipeline_name', 'pipeline_stage_id', 'pipeline_stage_name', 'contact_field_name', 'contact_field_id', 'contact_property_field_id', 'contact_property_field_name', 'fields_map'];


    public function property()
    {
        return $this->hasOne(CloudbedsProperty::class, 'property_id', 'property_id');
    }

    public function location()
    {
        return $this->hasOne(GhlLocation::class, 'location_id', 'location_id');
    }

    public function setFieldsMapAttribute($value)
    {
        $this->attributes['fields_map'] = json_encode($value);
    }

    public function getFieldsMapAttribute($value): array
    {
        return json_decode($value, true);
    }
}

@extends('layout')

@section('section')

<div class="container mt-5">
    <!-- Property and Location Details Section -->
    <div class="mb-4 p-3 bg-light rounded">
        <h4 class="mb-3">Mapping Details</h4>
        <p class="mb-1"><strong>Property:</strong> {{ $map->property->name }}</p>
        <p class="mb-1"><strong>Location:</strong> {{ $map->location->name }}</p>
        <p class="mb-1"><strong>Current Pipeline:</strong> <span class="cur_pipeline">{{ $map->pipeline_name }}</span></p>
        <p class="mb-1"><strong>Current Pipeline Stage:</strong> <span class="cur_pipeline_stage">{{ $map->pipeline_stage_name }}</span></p>
        <p class="mb-0"><strong>Contact Field:</strong> <span class="contact_field">{{ $map->contact_field_name }}</span></p>
        <p class="mb-0"><strong>Contact Property Field:</strong> <span class="contact_field">{{ $map->contact_property_field_name }}</span></p>
    </div>


    <form action="{{ route('map.save',['id' => $map->id])}}" method="POST" onsubmit="return setPipelineName(this)">
        @csrf
        <input type="hidden" name="property_id" value="{{$map->property_id }}">
        <input type="hidden" name="location_id" value="{{$map->location_id }}">
        <input type="hidden" name="pipeline_name" value="">
        <input type="hidden" name="pipeline_stage_name" value="">
        <input type="hidden" name="contact_field_name" value="">
        <input type="hidden" name="contact_property_field_name" value="">





        <div class="row">
            <div class="col-6 mt-2">
                <label for="pipeline_id">Select Pipeline</label>
                <select class="form-control" name="pipeline_id" id="pipeline_id" required>
                    @foreach ($pipelines as $pipeline)
                    <option value="{{ $pipeline['id']}}">{{ $pipeline['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 mt-2">
                <label for="pipeline_stage_id">Select Pipeline Stage Id</label>
                <select class="form-control" name="pipeline_stage_id" id="pipeline_stage_id" required>
                    @foreach (reset($pipelines)['stages'] as $stage )
                    <option value="{{ $stage['id'] }}">{{ $stage['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col mt-2">
                <label for="pipeline_id">Contact Cloudbeds Refrence On GHL</label>
                <select class="form-control" name="contact_field_id" id="contact_field_id" required>
                    @foreach ($fields as $field)
                    {{-- @if($field['fieldKey'] == 'contact.cb_reference') --}}
                    <option value="{{ $field['id']}}">{{ $field['name'] }}</option>
                    {{-- @endif --}}
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col mt-2">
                <label for="pipeline_id">Contact Property Refrence On GHL</label>
                <select class="form-control" name="contact_property_field_id" id="contact_property_field_id" required>
                    @foreach ($fields as $field)
                    {{-- @if($field['fieldKey'] == 'contact.cb_reference') --}}
                    <option value="{{ $field['id']}}">{{ $field['name'] }}</option>
                    {{-- @endif --}}
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col mt-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">SAVE</button>
            </div>
        </div>
    </form>
</div>
@endsection
@section('script')
<script>
    const pipelines = @json($pipelines);
    const fields = @json($fields);

    $('#pipeline_id').on('change', function() {

        let selectedPipelineId = $(this).val();
        // Find the pipeline by ID
        let pipeline = pipelines.find(item => item.id === selectedPipelineId);
        let html = '';
        // Check if pipeline exists and has stages
        if (pipeline && pipeline.stages) {
            pipeline.stages.forEach(stage => {
                html += `<option value="${stage.id}">${stage.name}</option>`;
            });
        }
        // Populate the select element
        $('#pipeline_stage_id').html(html);
    })

    function setPipelineName(form) {
        let selectedPipelineId = $('#pipeline_id').val();
        let selectedPipelineStageId = $('#pipeline_stage_id').val();
        let selectedContactFieldId = $('#contact_field_id').val();
        let selectedContactPropertyFieldId = $('#contact_property_field_id').val();
        let pipeline = pipelines.find(item => item.id === selectedPipelineId);
        let stage = pipeline.stages.find(item => item.id === selectedPipelineStageId);
        let fieldName = fields.find(field => field.id === selectedContactFieldId);
        let propertyFieldName = fields.find(field => field.id === selectedContactPropertyFieldId);
        $('input[name=pipeline_name]').val(pipeline.name);
        $('input[name=pipeline_stage_name]').val(stage.name);
        $('input[name=contact_field_name]').val(fieldName.name);
        $('input[name=contact_property_field_name]').val(propertyFieldName.name);
        return confirm('Are You Sure?');
    }

</script>

@endsection

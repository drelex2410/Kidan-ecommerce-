@php
    $label = $label ?? translate('Item');
@endphp

<div class="border rounded p-3 mb-2 feature-item" data-feature-index="{{ $featureIndex }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>{{ $label }}</strong>
        <button type="button" class="btn btn-soft-danger btn-sm remove-feature-item">{{ translate('Remove') }}</button>
    </div>
    <div class="form-group">
        <label>{{ translate('Title / Question / Address') }}</label>
        <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][title]" value="{{ $item['title'] ?? '' }}">
    </div>
    <div class="form-group">
        <label>{{ translate('Description / Answer') }}</label>
        <textarea class="form-control" rows="4" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][description]">{{ $item['description'] ?? '' }}</textarea>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ translate('Meta / Label') }}</label>
                <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][meta]" value="{{ $item['meta'] ?? '' }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ translate('Sub Meta') }}</label>
                <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][submeta]" value="{{ $item['submeta'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="form-row">
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ translate('Button Text') }}</label>
                <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][button_text]" value="{{ $item['button_text'] ?? '' }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>{{ translate('Button Link') }}</label>
                <input type="text" class="form-control" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][button_link]" value="{{ $item['button_link'] ?? '' }}">
            </div>
        </div>
    </div>
    <div class="form-group mb-0">
        <label>{{ translate('Image') }}</label>
        <div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false">
            <div class="input-group-prepend">
                <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse') }}</div>
            </div>
            <div class="form-control file-amount">{{ translate('Choose File') }}</div>
            <input type="hidden" name="sections[{{ $index }}][settings][items][{{ $featureIndex }}][image]" class="selected-files" value="{{ $item['image'] ?? '' }}">
        </div>
        <div class="file-preview box sm"></div>
    </div>
</div>

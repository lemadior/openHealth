{{-- @php
    $action = '';

    $divisionType = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($this->division->type);
@endphp --}}

@extends('livewire.healthcare-service.template.healthcare-service')

{{-- @section('title')
        {{ __('Послуги') }}
@endsection

@section('description')
    {{  $divisionType }} "{{ $division->name }}"
@endsection --}}

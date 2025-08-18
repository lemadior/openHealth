@php
    // $action = 'show';

    // $divisionType = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($this->division->type);

    // $showModal = true;
@endphp

@extends('livewire.healthcare-service.template.healthcare-service')

{{-- @section('title')
        {{ __('Послуги') }}
@endsection

@section('description')
    {{  $currentDivision['type'] }} "{{ $currentDivision['name'] }}"
@endsection --}}

@section('healthcare-modal')
    @include('livewire.healthcare-service.modals.healthcare-service-form', ['action' => 'show'])
@endsection

<?php

namespace App\Livewire\Division;

use App\Helpers\JsonHelper;
use App\Models\Division;
use App\Models\HealthcareService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class HealthcareServiceForm extends Component
{



    #[Validate([
        'healthcare_service.category' => 'required|min:6|max:255',
        'healthcare_service.providing_condition' => 'required',
        'healthcare_service.speciality_type' => 'required',
        'healthcare_service.type' => 'required_if:healthcare_service.category,PHARMACY_DRUGS',
    ])]

    public ?array $healthcare_service = [] ;
    public  ?object  $healthcare_services;

    public Division $division;

    public  string $mode = 'create';
    public ?array $tableHeaders = [];

    public ?array $dictionaries =[];
    public bool $showModal = false;
    public ?array $available_time = [];

    public ?array $speciality_type_key = ["THERAPIST", "PEDIATRICIAN", "FAMILY_DOCTOR",'RECEPTIONIST'];

    public ?array $speciality_type;
    /**
     * @var true
     */
    public bool $license_show;

    public function mount(Division $division)
    {
        $this->division = $division;
        $this->getHealthcareServices() ;
        $this->dictionaries = JsonHelper::searchValue('DICTIONARIES_PATH', [
            'HEALTHCARE_SERVICE_CATEGORIES',
            'SPECIALITY_TYPE',
            'HEALTHCARE_SERVICE_SPECIALITY_TYPE_FIELD_REQUIRED_FOR_CATEGORIES',
            'HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES',
            'PROVIDING_CONDITION',
        ]);
        $this->speciality_type = $this->dictionaries['SPECIALITY_TYPE'];
        $this->specialityType();
        $this->tableHeadersHealthcare();
    }

    public function getHealthcareServices():void
    {
        $this->healthcare_services = $this->division->healthcare_service;
    }

    public function openModal():void
    {
        $this->showModal = true;
    }

    public function closeModal():void
    {
        $this->showModal = false;
    }

    public  function create():void{
        $this->openModal();
    }

    public function store()
    {

        $this->resetErrorBag();
        $this->validate();
        $healthcare_service = new HealthcareService($this->healthcare_service);
        $this->division->healthcare_service()->save($healthcare_service);
        $this->healthcare_service = [];
        $this->getHealthcareServices();
        $this->closeModal();
    }


    public function  changeCategory():void
    {
        if (isset($this->healthcare_service['category']) && $this->healthcare_service['category'] == 'PHARMACY_DRUGS') {
            $this->speciality_type_key = ["PHARMACIST", "PROVISOR", "CLINICAL_PROVISOR"];
            $this->specialityType();
            $this->license_show = true;
        }
    }

    public function specialityType()
    {
        $this->dictionaries['SPECIALITY_TYPE'] = array_intersect_key( $this->speciality_type, array_flip($this->speciality_type_key));
    }

    public function edit($id){
        $this->mode = 'edit';
        $this->healthcare_service = HealthcareService::findorFail($id)->toArray();
        $this->openModal();
    }

    public function update()
    {
        HealthcareService::findorFail($this->healthcare_service['id'])->update($this->healthcare_service);
    }

    public function tableHeadersHealthcare(): void
    {
        $this->tableHeaders  = [
            __('ID E-health '),
            __('Категорія'),
            __('Умови надання'),
            __('Тип спеціальності'),
            __('Статус'),
            __('Дія'),
        ];
    }


    public function addAvailableTime($k = 0)
    {
         $this->healthcare_service['available_time'][] = [
            'days_of_week' => get_day_key($k),
            'all_day' => '',
            'available_start_time' =>'',
            'available_end_time' =>'',
        ];
    }


    public function removeAvailableTime($k)
    {
        unset($this->healthcare_service['available_time'][$k]);
    }
    public function render()
    {
        return view('livewire.division.healthcare-service-form');
    }
}

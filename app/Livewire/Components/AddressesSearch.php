<?php

namespace App\Livewire\Components;

use App\Classes\eHealth\Api\AdressesApi;
use App\Helpers\JsonHelper;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Illuminate\Validation\Rule;

class AddressesSearch extends Component
{




    public ?array $regions;

    public ?array $districts;

    public ?array $settlements;

    public ?array $streets;



    public string $area = '';

    public string $region = '';

    public string $settlement = '';

    public string $settlement_type = '';
    public string $settlement_id = '';

    public string $street_type = '';

    public string $street = '';

    public string $building = '';
    public string $apartment = '';

    public string $zip = '';

    public string $class = '';

    public ?array $dictionaries;

    protected $listeners = ['fetchAddressData' => 'provideAddressData','setAddressesFields'];

    protected function rules()
    {
        return [
            'area' => 'required',
            'region' => [
                Rule::requiredIf(function () {
                    return $this->area != 'М.КИЇВ';
                }),
            ],
            'settlement' => 'required',
            'settlement_type' => 'required',
            'settlement_id' => 'required',
            'street_type' => 'required',
        ];
    }


    public function mount($addresses,$class)
    {

        if (!empty($addresses)) {
            $this->updatedFields($addresses);
        }

        $this->class = $class;

        $this->regions = AdressesApi::_regions();
        $this->dictionaries = JsonHelper::searchValue('DICTIONARIES_PATH', [
            'SETTLEMENT_TYPE',
            'STREET_TYPE',
        ]);
    }

    public function updatedFields($addresses)
    {
        foreach ($addresses as $key => $address) {
            if (!empty($address)) {
                $this->{$key} = $address;
            }
        }
    }

    public function updatedArea($value)
    {
        if ($value === 'М.КИЇВ') {
            $this->region = '';
            $this->settlement = 'Київ';
            $this->settlement_type = 'CITY';
            $this->settlement_id = 'adaa4abf-f530-461c-bcbf-a0ac210d955b';
        }
    }
    public function setAddressesFields($addresses)
    {
        $this->updatedFields($addresses);
    }

    public function updated($field,)
    {

        $fieldsToReset = [];

        switch ($field) {
            case 'area':
                $fieldsToReset = ['region', 'settlement', 'settlement_id', 'settlement_type', 'street_type', 'street', 'building', 'apartment', 'zip'];
                break;
            case 'region':
                $fieldsToReset = [ 'settlement', 'settlement_id', 'settlement_type', 'street_type', 'street', 'building', 'apartment', 'zip'];
                break;
            case 'settlement':
                $fieldsToReset = ['street_type', 'street', 'building', 'apartment', 'zip'];
                break;
            case 'street':
                $fieldsToReset = [ 'building', 'apartment', 'zip'];
                break;
            default:
                // Дополнительные условия обновления полей, если необходимо
                break;
        }

        foreach ($fieldsToReset as $fieldToReset) {
            $this->{$fieldToReset} = '';
        }

    }

    public function provideAddressData()
    {


        $this->validate();

        $addresses = [
            'country' => 'UA',
            'type' => 'RESIDENCE',
            'area' => $this->area,
            'region' => $this->region,
            'settlement' => $this->settlement,
            'settlement_type' => $this->settlement_type,
            'settlement_id' => $this->settlement_id,
            'street_type' => $this->street_type,
            'street' => $this->street,
            'building' => $this->building,
            'apartment' => $this->apartment,
            'zip' => $this->zip,
        ];
        $this->dispatch('addressDataFetched',$addresses);
    }




    public function getDisstricts():void
    {

        if (empty($this->area)) {
            return;
        }
        $this->districts = AdressesApi::_districts($this->area, $this->region);

    }


    public function getSettlements():void{

        if (empty($this->region)) {
            return;
        }

        $this->settlements = AdressesApi::_settlements(
            $this->area,
            $this->region,
            $this->settlement);
    }


    public function getStreets():void
    {
        if (empty($this->settlement_id)) {
            return;
        }

        $this->streets = AdressesApi::_streets(
            $this->settlement_id,
            $this->street_type,
            $this->street
        );
    }

    public function render()
    {
        return view('livewire.components.addresses-search');
    }
}

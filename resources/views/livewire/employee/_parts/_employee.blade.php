<div>
    <div class=" py-4">
        <h3 class="font-medium text-2xl	 text-black dark:text-white">
            {{__('forms.personal_data')}}
        </h3>
    </div>
    <div class="mb-4.5 flex flex-col gap-6 xl:flex-row">

        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label for="last_name" class="default-label">
                    {{__('forms.last_name')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input class="default-input" wire:model="employee_request.employee.last_name" type="text"
                               id="last_name"/>
            </x-slot>
            @error('employee_request.employee.last_name')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label for="first_name" class="default-label">
                    {{__('forms.first_name')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input class="default-input" wire:model="employee_request.employee.first_name" type="text"
                               id="first_name"/>
            </x-slot>
            @error('employee_request.employee.first_name')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label for="second_name" class="default-label">
                    {{__('forms.second_name')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input class="default-input" wire:model="employee_request.employee.second_name" type="text"
                               id="second_name"/>
            </x-slot>
            @error('employee_request.employee.second_name')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>

    </div>
    <div class="mb-4.5 flex flex-col gap-6 xl:flex-row">
        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label for="birth_date" class="default-label">
                    {{__('forms.birth_date')}} *
                </x-forms.label>
            </x-slot>

            <x-slot name="input">
                <x-forms.input class="default-input" type="date" id="birth_date"
                               wire:model="employee_request.employee.birth_date"/>
            </x-slot>
            @error('employee_request.employee.birth_date')
            <x-slot name="error">

                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label for="email" class="default-label">
                    {{__('forms.email')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input class="default-input" wire:model="employee_request.employee.email" type="text"
                               id="email" placeholder="{{__('E-mail')}}"/>
            </x-slot>
            @error('employee_request.employee.email')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="xl:w-1/2">
            <x-slot name="label">
                <x-forms.label class="default-label" for="tax_id">
                    {{__('forms.number')}} {{__('forms.RNOCPP')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input maxlength="10" class="default-input" checked
                               wire:model="employee_request.employee.tax_id" type="text" id="tax_id" name="tax_id"/>
            </x-slot>
            @error('employee_request.employee.tax_id')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
    </div>
    <div class="mb-4.5 flex flex-col  gap-6 xl:flex-row">
        <x-forms.form-group class="">
            <x-slot name="label">
                <x-forms.label for="position" class="default-label">
                    {{__('forms.position')}} *
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.select
                    class="default-input" wire:model="employee_request.employee.position" type="text"
                    id="position"
                >
                    <x-slot name="option">
                        <option>{{__('forms.select')}} {{__('forms.position')}}</option>
                        @foreach($this->dictionaries['POSITION'] as $k=>$position )
                            <option value="{{$k}}">{{$position}}</option>
                        @endforeach
                    </x-slot>
                </x-forms.select>
            </x-slot>
            @error('employee_request.employee.position')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="">
            <x-slot name="label">
                <x-forms.label for="employee_type" class="default-label">
                    {{__('forms.role')}}*
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.select
                    class="default-input" wire:model="employee_request.employee.employee_type" type="text"
                    id="employee_type"
                >
                    <x-slot name="option">
                        <option> {{__('forms.select')}} {{__('forms.role')}}</option>
                        @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k=>$employee_type)
                            <option value="{{$k}}">{{$employee_type}}</option>
                        @endforeach
                    </x-slot>
                </x-forms.select>

            </x-slot>
            @error('employee_request.employee.employee_type')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>

        <x-forms.form-group class="">
            <x-slot name="label">
                <x-forms.label for="start_date" class="default-label">
                    {{__('forms.start_date_work')}}
                </x-forms.label>
            </x-slot>
            <x-slot name="input">

                <x-forms.input class="default-input" type="date" id="start_date"
                               wire:model="employee_request.employee.start_date"
                />
            </x-slot>
            @error('employee_request.positions.start_date')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
    </div>
    <div class="mb-4.5 flex flex-col  gap-6 xl:flex-row">
        <x-forms.form-group class="xl:w-1/4">
            <x-slot name="label">
                <x-forms.label for="working_experience" class="default-label">
                    {{__('forms.working_experience')}}
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.input class="default-input" wire:model="employee_request.employee.working_experience"
                               type="text"
                               id="working_experience"/>
            </x-slot>
            @error('employee_request.employee.working_experience')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
        <x-forms.form-group class="xl:w-1/1">
            <x-slot name="label">
                <x-forms.label class="default-label" for="about_myself">
                    {{__('forms.about_myself')}}
                </x-forms.label>
            </x-slot>
            <x-slot name="input">
                <x-forms.textarea
                    class="default-input" checked wire:model="employee_request.employee.about_myself" type="text"
                    id="about_myself" name="tax_id"/>
            </x-slot>
            @error('employee_request.employee.about_myself')
            <x-slot name="error">
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
            </x-slot>
            @enderror
        </x-forms.form-group>
    </div>

    <div class="mb-4.5 flex flex-col gap-0 ">
        <x-forms.label class="default-label" name="label">
            {{__('forms.gender')}} *
        </x-forms.label>
        @isset($this->dictionaries['GENDER'])
            @foreach($this->dictionaries['GENDER'] as $k=>$gender)
                <x-forms.form-group class="flex items-center mb-4 flex-row-reverse	justify-end	">
                    <x-slot name="input">
                        <x-forms.input name="gender" wire:model="employee_request.employee.gender" type="radio"
                                       value="{{$k}}"
                                       id="gender_{{$k}}"/>
                    </x-slot>
                    <x-slot name="label">
                        <x-forms.label class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                                       name="label" for="gender_{{$k}}">
                            {{$gender}}
                        </x-forms.label>
                    </x-slot>
                </x-forms.form-group>
            @endforeach
        @endisset
        @error('employee_request.employee.gender')
        <x-forms.error>
            {{$message}}
        </x-forms.error>
        @enderror
    </div>
    <div class="mb-4.5  gap-0 gap-6 ">
        <x-forms.label name="label" class="default-label">
            {{__('forms.phones')}} *
        </x-forms.label>
        @if($phones)
            @foreach($phones as $key=>$phone)
                <x-forms.form-group class="mb-2">
                    <x-slot name="label">
                        <div class="flex-row flex gap-6 items-center">
                            <div class="w-1/4">
                                <x-forms.select wire:model.defer="employee_request.employee.phones.{{$key}}.type"
                                                class="default-select">
                                    <x-slot name="option">
                                        <option>{{__('forms.typeMobile')}}</option>
                                        @foreach($this->dictionaries['PHONE_TYPE'] as $k=>$phone_type)
                                            <option value="{{$k}}">{{$phone_type}}</option>
                                        @endforeach
                                    </x-slot>
                                </x-forms.select>
                                @error("employee_request.employee.phones.{$key}.type")
                                <x-forms.error>
                                    {{$message}}
                                </x-forms.error>
                                @enderror
                            </div>
                            <div class="w-1/2">
                                <x-forms.input x-mask="+380999999999" class="default-input"
                                               wire:model="employee_request.employee.phones.{{$key}}.number" type="text"
                                               placeholder="{{__('+ 3(80)00 000 00 00 ')}}"/>

                                @error("employee_request.employee.phones.{$key}.number")
                                <x-forms.error>
                                    {{ $message }}
                                </x-forms.error>
                                @enderror
                            </div>
                            <div class="w-1/4">
                                @if($key != 0)
                                    <a wire:click="removePhone({{$key}})"
                                       class="text-primary m-t-5"
                                       href="#">{{__('forms.removePhone')}}</a>
                                @endif
                            </div>
                        </div>
                    </x-slot>
                </x-forms.form-group>
            @endforeach
        @endif

        <a wire:click="addRowPhone" class="text-primary m-t-5"
           href="#">{{__('forms.addPhone')}}</a>
    </div>


    <div class="mb-4.5 mt-4.5 flex flex-col gap-6 xl:flex-row justify-between items-center ">
        <div class="xl:w-1/4 text-left">

        </div>
        <div class="xl:w-1/4 text-right">
            <x-button wire:click="store('employee')" type="submit" class="btn-primary d-flex max-w-[150px]">
                {{__('forms.save')}}
            </x-button>
        </div>
    </div>

</div>

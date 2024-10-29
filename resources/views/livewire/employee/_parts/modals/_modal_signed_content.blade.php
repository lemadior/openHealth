
<x-dialog-modal maxWidth="3xl" class="w-3 h-full" wire:model.live="showModal">
    <x-slot name="title">
        {{__('Додати Спеціальність')}}
    </x-slot>
    <x-slot name="content">
        <div class="mb-4.5 flex flex-col gap-6 xl:flex-container">

            <x-forms.form-group class="xl:w-1/2">
                <x-slot name="label">
                    <x-forms.label class="default-label" for="knedp"
                                   name="label">
                        {{__('forms.KNEDP')}} *
                    </x-forms.label>
                </x-slot>
                <x-slot name="input">
                    <x-forms.select   class="default-input"
                                      wire:model="knedp"
                                      id="knedp">
                        <x-slot name="option">
                            @foreach($getCertificateAuthority as $k =>$certificate_type)
                                <option   value="{{$certificate_type['id']}}">{{$certificate_type['name']}}</option>
                            @endforeach
                        </x-slot>
                    </x-forms.select>
                </x-slot>
                @error("knedp")
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
                @enderror
            </x-forms.form-group>

            <x-forms.form-group class="xl:w-1/2">
                <x-slot name="label">
                    <x-forms.label class="default-label" for="keyContainerUpload"
                                   name="label">
                        {{__('forms.keyContainerUpload')}} *
                    </x-forms.label>
                </x-slot>
                <x-slot name="input">
                    <x-forms.input class="default-input" wire:model="file"
                                   type="file" id="keyContainerUpload"/>
                </x-slot>
                @error("keyContainerUpload")
                <x-forms.error>
                    {{$message}}
                </x-forms.error>
                @enderror
            </x-forms.form-group>

            <x-forms.form-group class="xl:w-1/2">
                <x-slot name="label">
                    <x-forms.label class="default-label" for="password"
                                   name="label">
                        {{__('forms.password')}} *
                    </x-forms.label>
                </x-slot>
                <x-slot name="input">
                    <x-forms.input class="default-input" wire:model="password"
                                   type="password" id="password"/>
                </x-slot>
            </x-forms.form-group>

            <div class="mb-4.5 flex flex-col gap-6 xl:flex-row justify-between items-center ">
                <div class="xl:w-1/4 text-left">
                    <x-secondary-button wire:click="closeModalModel()">
                        {{__('Назад')}}
                    </x-secondary-button>
                </div>
                <div class="xl:w-1/4 text-right">
                    <button wire:click="sendApiRequest()" type="button" class="btn-primary">
                        {{__('Відправити на затвердження ')}}
                    </button>
                </div>
            </div>

        </div>
    </x-slot>
</x-dialog-modal>

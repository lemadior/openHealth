<div class="mb-4">
    <div class="border-b border-stroke  py-4 dark:border-strokedark">
        <h3 class="font-medium text-2xl	  dark:text-white">
            {{__('forms.education')}}
        </h3>
    </div>
    <table class="w-full table-auto">
        <thead>
        <tr class="bg-gray-2 text-left dark:bg-meta-4">
            <th class="min-w-[220px] px-4 py-4 font-medium text-black dark:text-white xl:pl-11">
                {{__('forms.institution_name')}}
            </th>
            <th class="min-w-[150px] px-4 py-4 font-medium text-black dark:text-white">
                {{__('forms.speciality')}}
            </th>
            <th class="min-w-[120px] px-4 py-4 font-medium text-black dark:text-white">
                {{__('forms.issued_date')}}

            </th>
            <th class="px-4 py-4 font-medium text-black dark:text-white">
                {{__('forms.certificate')}}
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @isset($employee->educations)
        @foreach($employee->educations as $k=>$education)
            <tr>
                <td class="border-b border-[#eee] px-4 py-5 pl-9 dark:border-strokedark xl:pl-11">
                    {{$education['institution_name'] ?? ''}}
                </td>
                <td class="border-b border-[#eee] px-4 py-5 dark:border-strokedark">
                    {{$education['speciality'] ?? ''}}
                </td>
                <td class="border-b border-[#eee] px-4 py-5 dark:border-strokedark">
                    {{$education['issued_date'] ?? ''}}
                </td>
                <td class="border-b border-[#eee] px-4 py-5 dark:border-strokedark">
                    {{$education['degree'] ?? ''}}
                </td>
                <td class="border-b border-[#eee] px-4 py-5 dark:border-strokedark">
                    <a wire:click.prevent="edit('educations',{{$k}})" href="">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </a>
                </td>
            </tr>
        @endforeach
        @endisset
        </tbody>
    </table>
    <div class="mb-6 mt-6 flex flex-wrap gap-5 xl:gap-7.5">
        <a wire:click.prevent="create('educations')" class="text-primary" href="">{{__('forms.add_education')}} </a>
    </div>

</div>
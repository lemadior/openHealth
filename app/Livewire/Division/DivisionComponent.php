<?php

declare(strict_types=1);

namespace App\Livewire\Division;


use Arr;
use App\Enums\Status;
use Livewire\Component;
use App\Traits\FormTrait;
use App\Livewire\Division\Forms\DivisionForm;
use App\Models\Division;
use App\Repositories\Repository;
use App\Traits\WorkTimeUtilities;
use App\Classes\eHealth\Api\Division as DivisionApi;

class DivisionComponent extends Component
{
    use FormTrait,
        WorkTimeUtilities;

    /**
     * The form model instance for handling division data.
     *
     * @var DivisionForm
     */
    public DivisionForm $divisionForm;

    /**
     * Array containing dictionary names only used within the component.
     *
     * @var array
     */
    public array $dictionaryNames = [
        'DIVISION_TYPE',
        'SETTLEMENT_TYPE',
        'PHONE_TYPE',
        'DIVISION_TYPE'
    ];

    /**
     * Handles data type conversion for location coordinates after component hydration.
     *
     * This method is automatically called by Livewire after the component receives data
     * from the browser but before the data is applied to the component's properties.
     * It ensures that latitude and longitude values are always stored as float type,
     * even if they come from the form as strings.
     *
     * - If a coordinate value is empty, it's converted to 0
     * - If a value exists, it's properly cast to float
     *
     * @return void
     */
    public function hydrate()
    {
        $this->divisionForm->division['location']['latitude'] =
            (float) (empty($this->divisionForm->division['location']['latitude'])
                ? 0
                : $this->divisionForm->division['location']['latitude']);

        $this->divisionForm->division['location']['longitude'] =
            (float) (empty($this->divisionForm->division['location']['longitude'])
                ? 0
                : $this->divisionForm->division['location']['longitude']);
    }

    /**
     * Proxy method!
     * Proceed data when day is off and hasn't the schedule at all
     *
     * @param  mixed  $day
     * @param  mixed  $allDayWork
     *
     * @return void
     */
    public function notWorking($day, $allDayWork)
    {
        $this->divisionForm->notWorking($day, $allDayWork);
    }

    /**
     * Proxy method!
     * Add shift(s) to the current day's schedule
     *
     * @param  string  $day
     *
     * @return void
     */
    public function addAvailableShift(string $day): void
    {
        $this->divisionForm->addAvailableShift($day);
    }

    /**
     * Proxy method!
     * Remove the selected shift from the day's schedule
     *
     * @param  string  $day  key value aka 'mon', 'tue' etc.
     * @param  int  $shift  shift's numeric position in array
     *
     * @return void
     */
    public function deleteShift(string $day, int $shift)
    {
        $this->divisionForm->deleteShift($day, $shift);
    }

    /**
     * Proxy method!
     * Called when no shift should be present in the day's schedule.
     * But one time range must left anyway!
     *
     * @param  mixed  $day
     * @param  mixed  $isShift  true if shift schedule is activated
     * @return void
     */
    public function noShift($day, $isShift)
    {
        $this->divisionForm->noShift($day, $isShift);
    }

    /**
     * Sets the dictionary for this component.
     *
     * @return static Returns the current instance for method chaining
     */
    protected function setDictionary(): static
    {
        $this->getDictionary();

        return $this;
    }

    /**
     * Validate the data coming from the form(s)
     *
     * @return bool
     */
    public function validateDivision(): bool
    {
        $error = $this->divisionForm->doValidation();

        if ($error) {
            session()->flash('error', $error);

            return false;
        } else {
            return true;
        }
    }

    /**
     * Prepares and normalizes division data for an outgoing API request.
     *
     * This method:
     * - Removes the 'legal_entity_id' key from the division form data (as it is not needed in the request)
     * - Passes the division data through the schema service for normalization and snake_case conversion
     * - Removes any empty keys from the resulting array
     *
     * @return array The normalized and cleaned division data ready for API request
     */
    protected function prepareRequestData(): array
    {
        // This key as is don't need here. But schema has the same key means the legalEntity_uuid
        Arr::forget($this->divisionForm->division, 'legal_entity_id');

        $divisionData = schemaService()
                    ->setDataSchema($this->divisionForm->division, app(DivisionApi::class))
                    ->requestSchemaNormalize('schemaRequest')
                    ->snakeCaseKeys(true)
                    ->getNormalizedData();

        return removeEmptyKeys($divisionData);
    }

    /**
     * Store division data to the database
     *
     * @return null|Division
     */
    protected function saveToDB(): ?Division
    {
        $division = null;

        $this->divisionForm->division['status'] =  empty($this->divisionForm->division['uuid'])
            ? Status::DRAFT->value
            : Status::UNSYNCED->value;

        $division = Repository::division()->saveDivisionData($this->divisionForm->division, legalEntity());

        return $division;
    }

    /**
     * Filters an array of dictionaries based on allowed items.
     *
     * @param array $source The source array of dictionaries to filter
     * @param array $allowedItems Array of allowed items to filter by
     *
     * @return array The filtered array containing only allowed items
     */
    protected function filterDictionaries(array $source, array $allowedItems): array
    {
        $arr = [];

        foreach ($source as $key => $dictionary) {

            if (in_array($key, array_keys($allowedItems))) {
                $arr[$key] = array_filter($dictionary, fn($item) => in_array($item, $allowedItems[$key]), ARRAY_FILTER_USE_KEY);

                continue;
            }

            $arr[$key] = $dictionary;
        }

        return $arr;
    }
}

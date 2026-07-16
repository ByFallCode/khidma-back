<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class AssignmentRequest extends ApiRequest
{
    private const RESPONSIBILITIES = ['RESPONSABLE_RESIDENCE', 'ACCUEILLANT', 'RESPONSABLE_DELEGATION', 'CHEF_CHAMBRE'];

    private const DAYS = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];

    public function rules(): array
    {
        return [
            'agentId' => ['required', 'integer', 'exists:users,id'],
            'residenceId' => ['required', 'integer', 'exists:residences,id'],
            'responsibilities' => ['required', 'array', 'min:1'],
            'responsibilities.*' => [Rule::in(self::RESPONSIBILITIES)],
            'startDate' => ['nullable', 'date_format:Y-m-d'],
            'endDate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:startDate'],
            'rotationSlots' => ['nullable', 'array'],
            'rotationSlots.*.dayOfWeek' => ['nullable', Rule::in(self::DAYS)],
            'rotationSlots.*.fromTime' => ['required', 'date_format:H:i'],
            'rotationSlots.*.toTime' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'agentId.required' => 'ASSIGNMENT_AGENT_REQUIRED',
            'residenceId.required' => 'ASSIGNMENT_RESIDENCE_REQUIRED',
            'responsibilities.required' => 'ASSIGNMENT_RESPONSIBILITIES_REQUIRED',
            'responsibilities.min' => 'ASSIGNMENT_RESPONSIBILITIES_REQUIRED',
        ];
    }
}

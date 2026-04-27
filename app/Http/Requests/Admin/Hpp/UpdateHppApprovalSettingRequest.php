<?php

namespace App\Http\Requests\Admin\Hpp;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHppApprovalSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'planner_control_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'counter_part_unit_work_id' => ['nullable', 'integer', 'exists:unit_works,id'],
            'counter_part_section_id' => ['nullable', 'integer', 'exists:unit_work_sections,id'],
            'dirops_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $unitId = $this->input('counter_part_unit_work_id');
            $sectionId = $this->input('counter_part_section_id');

            if ($sectionId && ! $unitId) {
                $validator->errors()->add('counter_part_unit_work_id', 'Pilih unit work Counter Part terlebih dahulu.');
            }

            if ($unitId && $sectionId) {
                $exists = \App\Models\UnitWorkSection::query()
                    ->where('id', $sectionId)
                    ->where('unit_work_id', $unitId)
                    ->exists();

                if (! $exists) {
                    $validator->errors()->add('counter_part_section_id', 'Seksi Counter Part tidak sesuai dengan Unit Work yang dipilih.');
                }
            }
        });
    }
}
<?php

namespace App\Http\Requests\Inspector;

use App\Models\EquipmentInspection;
use App\Models\User;
use App\Policies\EquipmentInspectionPolicy;
use App\Support\Inspector\EquipmentFormCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveEquipmentInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $inspection = $this->route('inspection');

        return $this->user()?->hasRole(User::ROLE_INSPECTOR)
            && (! $inspection instanceof EquipmentInspection
                || (new EquipmentInspectionPolicy)->update($this->user(), $inspection));
    }

    protected function prepareForValidation(): void
    {
        $answers = $this->input('answers');
        if (! is_array($answers)) {
            return;
        }

        foreach ($answers as &$answer) {
            if (is_array($answer) && is_string($answer['remark'] ?? null)) {
                $answer['remark'] = preg_replace('/^[\s\p{Z}]+|[\s\p{Z}]+$/u', '', $answer['remark']);
            }
        }
        unset($answer);
        $this->merge(['answers' => $answers]);
    }

    public function rules(): array
    {
        $inspection = $this->route('inspection');
        if ($inspection instanceof EquipmentInspection) {
            $keys = $inspection->answers()->pluck('item_key')->all();
        } else {
            $form = EquipmentFormCatalog::find((string) $this->route('equipmentForm'));
            abort_if($form === null, 404);
            $keys = collect($form['groups'])->flatMap(fn (array $group): array => $group['items'])->pluck('id')->all();
        }

        $allowedKeys = implode(',', $keys);
        $rules = [
            'inspection_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now(config('app.timezone'))->toDateString()],
            'lock_version' => [$inspection ? 'required' : 'nullable', 'integer', 'min:1'],
            'answers' => ['required', 'array:'.$allowedKeys, 'size:'.count($keys)],
            'photos' => ['sometimes', 'array:'.$allowedKeys],
            'photos.*' => ['array', 'max:3'],
            'photos.*.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'extensions:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4096,max_height=4096'],
            'delete_attachments' => ['sometimes', 'array', 'max:100'],
            'delete_attachments.*' => ['required', 'integer', 'distinct'],
        ];

        foreach ($keys as $key) {
            $rules['answers.'.$key] = ['required', 'array:rating,remark'];
            $rules['answers.'.$key.'.rating'] = ['present', 'nullable', Rule::in(['A', 'B', 'C'])];
            $rules['answers.'.$key.'.remark'] = ['present', 'nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $size = collect($this->allFiles()['photos'] ?? [])->flatten()
                ->sum(fn ($file): int => $file instanceof UploadedFile && $file->isValid() ? (int) $file->getSize() : 0);
            if ($size > 10 * 1024 * 1024) {
                $validator->errors()->add('photos', 'Total foto dalam satu penyimpanan maksimal 10 MB. Simpan foto secara bertahap.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'inspection_date.before_or_equal' => 'Tanggal pemeriksaan tidak boleh di masa depan.',
            'inspection_date.date_format' => 'Tanggal pemeriksaan tidak valid.',
            'answers.*.rating.in' => 'Kondisi harus A, B, atau C.',
            'answers.*.remark.max' => 'Keterangan maksimal 2.000 karakter.',
            'photos.*.max' => 'Maksimal 3 foto per item.',
            'photos.*.*.max' => 'Ukuran setiap foto maksimal 2 MB.',
        ];
    }
}

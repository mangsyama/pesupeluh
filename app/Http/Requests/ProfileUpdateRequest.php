<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:100',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'nip' => [
                'required',
                'digits:18',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone_number' => ['nullable', 'regex:/^\d+$/', 'max:15'],
            'profile_photo' => ['nullable', function ($attribute, $value, $fail) {
                if (request()->hasFile('profile_photo')) {
                    $file = request()->file('profile_photo');
                    if (!str_starts_with($file->getMimeType(), 'image/')) {
                        $fail('File profile photo harus berupa gambar.');
                    }
                } elseif (is_string($value)) {
                    if ($value !== 'delete' && !str_starts_with($value, 'data:image/')) {
                        $fail('File profile photo harus berupa gambar.');
                    }
                } else {
                    $fail('File profile photo harus berupa gambar.');
                }
            }],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Contact Request
 *
 * This request handles the contact form validation.
 *
 * @package App\Http\Requests
 * @author Renatus Bernardo
 */
class ContactRequest extends FormRequest
{
    /**
     * Determine whether the current user is authorized to perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules applied to the incoming request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Define custom validation error messages for the request.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name so we know who is contacting us.',
            'email.required' => 'We need an email address to respond.',
            'email.email' => 'Please provide a valid email address.',
            'subject.required' => 'Add a short subject so we can route your message.',
            'message.required' => 'Let us know how we can help you.',
        ];
    }
}

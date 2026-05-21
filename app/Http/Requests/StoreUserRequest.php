<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,project_manager,team_member,client',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'User name is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
        ];
    }
}

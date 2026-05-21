<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('update', $this->user);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = $this->user;
        
        return [
            'name' => 'required|string|max:255',
            'email' => "required|email|max:255|unique:users,email,$user->id",
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => 'required|in:admin,project_manager,team_member,client',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'User name is required',
            'email.required' => 'Email address is required',
            'email.unique' => 'This email is already in use by another user',
            'password.confirmed' => 'Password confirmation does not match',
            'role.required' => 'Please select a role',
            'role.in' => 'Invalid role selected',
        ];
    }
}

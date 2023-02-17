<?php

namespace Uwla\Lacl\Http\Requests;

// use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->route('user')->id;

        return [
            'name' => 'required|string|min:3|max:30',
            'email' => "required|email|unique:users,email,{$id}",
            'password' => 'required|string|min:8|max:80',
        ];
    }
}

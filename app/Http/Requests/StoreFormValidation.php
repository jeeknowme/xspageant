<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormValidation extends FormRequest
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
        return [
            'candidate_id'=>'required|integer',
            'number'=>'required|integer|max:4',
        ];
    }

    public function messages()
    {
        return [
            'name.required'=>'Name is also required',
            'name.integer'=>'Name must be a number',
            'number.integer'=>'Candidate number must be a number',
            'number.required'=>'Candidate number is also required',
            'number.max'=>'Candidate number exceeded the limit'
        ];
    }
}

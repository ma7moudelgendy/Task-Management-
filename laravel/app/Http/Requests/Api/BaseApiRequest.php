<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseApiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}

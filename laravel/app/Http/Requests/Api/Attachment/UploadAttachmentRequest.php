<?php

namespace App\Http\Requests\Api\Attachment;

use App\Http\Requests\Api\BaseApiRequest;

class UploadAttachmentRequest extends BaseApiRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,zip,txt',
            ],
        ];
    }
}


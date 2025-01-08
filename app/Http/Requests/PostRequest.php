<?php

namespace Modules\Blog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if (request()->isMethod('post')) {
            return [
                'title'            => 'required|string|max:255',  // Title is nullable
                'publish'          => 'required|string|in:draft,published',  // Publish status is nullable, can be 'draft' or 'published'
                'featured'         => 'boolean',  // Boolean field
                'content'          => 'required|string',  // Content is nullable
                'description'      => 'required|string',  // nullable description

                'coverUrl' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        // Check if coverUrl is a file
                        if ($this->hasFile('coverUrl')) {
                            $file = $this->file('coverUrl');
                            if (!$file->isValid() || !in_array($file->extension(), ['jpeg', 'png', 'jpg', 'gif', 'webp'])) {
                                $fail('The cover image must be a valid image file (jpeg, png, jpg, gif, webp).');
                            } elseif ($file->getSize() > 2048 * 1024) {
                                $fail('The cover image file size must not exceed 2MB.');
                            }
                        }
                        // If it's not a file, check if it's a valid URL
                        elseif (is_string($value)) {
                            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                $fail('The cover image URL hfth is not valid.');
                            }
                        }
                    },
                ],

                'tags' => 'nullable|array', // Ensure it's an array
                'tags.*' => 'string', // Each tag should be a string

                'meta_title'       => 'required|string|max:255',  // Meta title is nullable
                'meta_keywords'    => 'nullable|array',  // JSON as an array in the request
                'meta_keywords.*'  => 'nullable|string|max:255',  // Each keyword should be a string
                'meta_description' => 'required|string|max:255',  // Meta description is nullable
            ];
        } else {
            return [
                'title'            => 'nullable|string|max:255',  // Title is nullable
                'publish'          => 'nullable|string|in:draft,published',  // Publish status nullable
                'featured'         => 'boolean',  // Boolean field
                'content'          => 'nullable',  // Content is nullable
                'description'      => 'nullable|string',  // nullable description
                'duration'         => 'nullable|string|max:255',  // nullable duration

                'coverUrl' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        // Check if coverUrl is a file
                        if ($this->hasFile('coverUrl')) {
                            $file = $this->file('coverUrl');
                            if (!$file->isValid() || !in_array($file->extension(), ['jpeg', 'png', 'jpg', 'gif', 'webp'])) {
                                $fail('The cover image must be a valid image file (jpeg, png, jpg, gif, webp).');
                            } elseif ($file->getSize() > 2048 * 1024) {
                                $fail('The cover image file size must not exceed 2MB.');
                            }
                        }
                        // If it's not a file, check if it's a valid URL
                        elseif (is_string($value)) {
                            if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                $fail('The cover image URL gftg is not valid.');
                            }
                        }
                    },
                ],

                'tags' => 'nullable|array', // Ensure it's an array
                'tags.*' => 'string', // Each tag should be a string

                'meta_title'       => 'nullable|string|max:255',  // Meta title is nullable
                'meta_keywords'    => 'nullable|array',  // JSON as an array in the request
                'meta_keywords.*'  => 'nullable|string|max:255',  // Each keyword should be a string
                'meta_description' => 'nullable|string|max:255',  // Meta description is nullable
            ];
        }
    }




    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // 'cover'            => $this->coverUrl,
            'author_id'        => $this->authorId,
            'meta_title'       => $this->metaTitle,
            'total_views'      => $this->totalViews,
            'total_shares'     => $this->totalShares,
            'meta_keywords'    => $this->metaKeywords,
            'total_favorites'  => $this->totalFavorites,
            'meta_description' => $this->metaDescription,
        ]);
    }


    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'coverUrl.required' => 'Cover image is required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}

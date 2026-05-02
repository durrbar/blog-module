<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Enum;
use Modules\Blog\Enums\PostPublishStatus;

class PostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            ...$this->baseRules(),
            ...$this->contentRules($isCreate),
            ...$this->metaRules($isCreate),
        ];
    }

    /**
     * Shared rules for create and update.
     */
    private function baseRules(): array
    {
        return [
            'featured' => 'boolean',
            'duration' => 'nullable|string|max:255',
            'coverUrl' => ['required', $this->coverUrlValidationRule()],
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'meta_keywords' => 'nullable|array',
            'meta_keywords.*' => 'nullable|string|max:255',
        ];
    }

    /**
     * Rules that differ between create and update.
     */
    private function contentRules(bool $isCreate): array
    {
        return [
            'title' => $isCreate ? 'required|string|max:255' : 'nullable|string|max:255',
            'publish' => [$isCreate ? 'required' : 'nullable', 'string', new Enum(PostPublishStatus::class)],
            'content' => $isCreate ? 'required|string' : 'nullable',
            'description' => $isCreate ? 'required|string' : 'nullable|string',
        ];
    }

    /**
     * Meta rules that differ between create and update.
     */
    private function metaRules(bool $isCreate): array
    {
        return [
            'meta_title' => $isCreate ? 'required|string|max:255' : 'nullable|string|max:255',
            'meta_description' => $isCreate ? 'required|string|max:255' : 'nullable|string|max:255',
        ];
    }

    /**
     * Custom cover validation to support either uploaded file or URL.
     */
    private function coverUrlValidationRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->hasFile('coverUrl')) {
                /** @var UploadedFile $file */
                $file = $this->file('coverUrl');

                if (! $file->isValid() || ! in_array($file->extension(), ['jpeg', 'png', 'jpg', 'gif', 'webp'], true)) {
                    $fail('The cover image must be a valid image file (jpeg, png, jpg, gif, webp).');

                    return;
                }

                if ($file->getSize() > 2048 * 1024) {
                    $fail('The cover image file size must not exceed 2MB.');
                }

                return;
            }

            if (is_string($value) && ! filter_var($value, FILTER_VALIDATE_URL)) {
                $fail('The cover image URL is not valid.');
            }
        };
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

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'author_id' => $this->authorId,
            'meta_title' => $this->metaTitle,
            'total_views' => $this->totalViews,
            'total_shares' => $this->totalShares,
            'meta_keywords' => $this->metaKeywords,
            'total_favorites' => $this->totalFavorites,
            'meta_description' => $this->metaDescription,
        ]);
    }
}

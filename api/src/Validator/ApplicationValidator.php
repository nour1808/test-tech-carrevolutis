<?php

declare(strict_types=1);

namespace App\Validator;

class ApplicationValidator
{
    public function validateApplyPayload(?array $data): array
    {
        $errors = [];

        if (!isset($data['offer_id']) || filter_var($data['offer_id'], FILTER_VALIDATE_INT) === false) {
            $errors['offer_id'] = 'offer_id is required and must be an integer';
        }

        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required';
        }

        if (!isset($data['cv_url']) || empty($data['cv_url'])) {
            $errors['cv_url'] = 'cv_url is required';
        } elseif (!filter_var($data['cv_url'], FILTER_VALIDATE_URL)) {
            $errors['cv_url'] = 'cv_url must be a valid URL';
        }

        return $errors;
    }
}

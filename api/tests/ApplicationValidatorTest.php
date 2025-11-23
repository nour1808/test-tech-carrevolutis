<?php

use App\Validator\ApplicationValidator;
use PHPUnit\Framework\TestCase;

class ApplicationValidatorTest extends TestCase
{
    private ApplicationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ApplicationValidator();
    }

    public function testValidPayloadReturnsNoErrors(): void
    {
        $errors = $this->validator->validateApplyPayload([
            'offer_id' => 123,
            'email' => 'user@example.com',
            'cv_url' => 'https://example.com/cv.pdf',
        ]);

        $this->assertSame([], $errors);
    }

    public function testInvalidPayloadReturnsErrors(): void
    {
        $errors = $this->validator->validateApplyPayload([
            'offer_id' => 'abc',
            'email' => 'invalid',
            'cv_url' => 'not-a-url',
        ]);

        $this->assertArrayHasKey('offer_id', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('cv_url', $errors);
    }
}

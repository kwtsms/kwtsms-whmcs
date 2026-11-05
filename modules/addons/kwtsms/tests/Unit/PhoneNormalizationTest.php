<?php

declare(strict_types=1);

namespace KwtSMS\WHMCS\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KwtSMS\WHMCS\SmsHelper;

/**
 * Tests for SmsHelper::normalizePhone().
 * Uses the kwtsms-php PhoneUtils under the hood.
 * No WHMCS runtime required.
 */
class PhoneNormalizationTest extends TestCase
{
    /**
     * @dataProvider phoneProvider
     */
    public function testNormalizePhone(string $input, string $countryCode, string $expected): void
    {
        $result = SmsHelper::normalizePhone($input, $countryCode);
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function phoneProvider(): array
    {
        // 2026-11-05 11:21 +03: mock numbers use 99912345678 (country code 999 is unassigned,
        // nobody holds it) instead of a sample Kuwait mobile that belongs to a real person;
        // normalizePhone() has no country rules, so the cases test the same logic, int-testnumber.
        return [
            'Local 8 digits' => ['12345678', '999', '99912345678'],
            'With country code' => ['99912345678', '999', '99912345678'],
            'With plus' => ['+99912345678', '999', '99912345678'],
            'With 00 prefix' => ['0099912345678', '999', '99912345678'],
            'With leading zero' => ['012345678', '999', '99912345678'],
            'Dashes and spaces' => ['00999-12-345-678', '999', '99912345678'],
            'Parentheses and dots' => ['(999) 1234.5678', '999', '99912345678'],
            'US number stays intact' => ['12025551234', '965', '12025551234'],
            'Short number gets country code' => ['5551234', '1', '15551234'],
            'Empty country code no prepend' => ['12345678', '', '12345678'],
            'Arabic digits' => ["\xD9\xA1\xD9\xA2\xD9\xA3\xD9\xA4\xD9\xA5\xD9\xA6\xD9\xA7\xD9\xA8", '999', '99912345678'],
        ];
    }

    public function testNormalizedPhoneIsDigitsOnly(): void
    {
        $result = SmsHelper::normalizePhone('+999 (1234) 5678', '999');
        $this->assertMatchesRegularExpression('/^\d+$/', $result);
    }

    public function testNormalizedPhoneHasNoLeadingZeros(): void
    {
        $result = SmsHelper::normalizePhone('00099912345678', '999');
        $this->assertStringStartsWith('999', $result);
    }
}

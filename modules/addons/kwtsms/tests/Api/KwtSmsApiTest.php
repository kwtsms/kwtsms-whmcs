<?php

declare(strict_types=1);

namespace KwtSMS\WHMCS\Tests\Api;

use PHPUnit\Framework\TestCase;
use KwtSMS\KwtSMS;
use KwtSMS\PhoneUtils;
use KwtSMS\MessageUtils;

/**
 * Live API tests against kwtSMS gateway with test=1.
 *
 * Requires KWTSMS_USERNAME and KWTSMS_PASSWORD env vars.
 * All sends use test=1 (messages queue but never deliver).
 * Phone: send tests read KWTSMS_TEST_NUMBER (965XXXXXXXX) and skip when it is unset.
 *
 * Run: KWTSMS_USERNAME=xxx KWTSMS_PASSWORD=yyy KWTSMS_TEST_NUMBER=965XXXXXXXX vendor/bin/phpunit --testsuite Api
 *
 * 2026-11-05 11:20 +03: send tests take the number from KWTSMS_TEST_NUMBER via testNumber()
 * instead of a hard-coded sample mobile (sample numbers belong to real people); pure
 * normalization checks use 99912345678 (country code 999 is unassigned), int-testnumber.
 */
class KwtSmsApiTest extends TestCase
{
    private ?KwtSMS $client = null;

    /**
     * Test number from KWTSMS_TEST_NUMBER; skips the test when unset or not a Kuwait mobile.
     * The value is never printed.
     */
    private function testNumber(): string
    {
        $number = (string) getenv('KWTSMS_TEST_NUMBER');
        if (preg_match('/^965[4569]\d{7}$/', $number) !== 1) {
            $this->markTestSkipped('KWTSMS_TEST_NUMBER unset or not 965XXXXXXXX');
        }
        return $number;
    }

    protected function setUp(): void
    {
        $username = getenv('KWTSMS_USERNAME');
        $password = getenv('KWTSMS_PASSWORD');

        if (empty($username) || empty($password)) {
            $this->markTestSkipped('KWTSMS_USERNAME and KWTSMS_PASSWORD env vars required for API tests');
        }

        // test_mode=true, no log file
        $this->client = new KwtSMS($username, $password, 'KWT-SMS', true, '');
    }

    public function testBalanceReturnsFloat(): void
    {
        $balance = $this->client->balance();

        $this->assertNotNull($balance, 'balance() should return a float, got null (bad credentials?)');
        $this->assertIsFloat($balance);
        $this->assertGreaterThanOrEqual(0.0, $balance);
    }

    public function testSenderIdsReturnsArray(): void
    {
        $result = $this->client->senderids();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertSame('OK', $result['result'], 'senderids() should return OK');
        $this->assertArrayHasKey('senderids', $result);
        $this->assertIsArray($result['senderids']);
        $this->assertNotEmpty($result['senderids'], 'Account should have at least one sender ID');
    }

    public function testCoverageReturnsArray(): void
    {
        $result = $this->client->coverage();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertSame('OK', $result['result'], 'coverage() should return OK');
        $this->assertArrayHasKey('prefixes', $result);
        $this->assertIsArray($result['prefixes']);
        $this->assertNotEmpty($result['prefixes'], 'Coverage list should not be empty');
    }

    public function testCoverageIncludesKuwait(): void
    {
        $result = $this->client->coverage();
        $prefixes = $result['prefixes'] ?? [];

        $hasKuwait = false;
        foreach ($prefixes as $prefix) {
            if (str_starts_with('965', (string) $prefix) || str_starts_with((string) $prefix, '965')) {
                $hasKuwait = true;
                break;
            }
        }
        $this->assertTrue($hasKuwait, 'Coverage should include Kuwait prefix 965');
    }

    public function testSendTestModeReturnsOk(): void
    {
        $response = $this->client->send($this->testNumber(), 'kwtSMS WHMCS module test message');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('result', $response);
        $this->assertSame('OK', $response['result'], 'send() with test=1 should return OK. Got: ' . json_encode($response));
        $this->assertArrayHasKey('msg-id', $response);
        $this->assertNotEmpty($response['msg-id']);
    }

    public function testSendTestModeReturnsMsgId(): void
    {
        $response = $this->client->send($this->testNumber(), 'Test msg-id check');

        $this->assertArrayHasKey('msg-id', $response);
        $this->assertIsString($response['msg-id']);
        $this->assertGreaterThan(0, strlen($response['msg-id']));
    }

    public function testSendTestModeReturnsBalanceAfter(): void
    {
        $response = $this->client->send($this->testNumber(), 'Test balance-after check');

        $this->assertArrayHasKey('balance-after', $response);
        $this->assertGreaterThanOrEqual(0, (float) $response['balance-after']);
    }

    public function testSendArabicMessage(): void
    {
        $arabicMsg = 'مرحبا، هذه رسالة تجريبية من kwtSMS WHMCS';
        $response = $this->client->send($this->testNumber(), $arabicMsg);

        $this->assertIsArray($response);
        $this->assertSame('OK', $response['result'], 'Arabic SMS should send OK in test mode');
    }

    public function testSendEmptyMessageFails(): void
    {
        $response = $this->client->send($this->testNumber(), '');

        // Library or API should reject empty messages
        $this->assertIsArray($response);
        if (isset($response['result'])) {
            $this->assertNotSame('OK', $response['result'], 'Empty message should not succeed');
        }
    }

    public function testPhoneUtilsNormalize(): void
    {
        $this->assertSame('99912345678', PhoneUtils::normalize_phone('+999-1234-5678'));
        $this->assertSame('99912345678', PhoneUtils::normalize_phone('0099912345678'));
        $this->assertSame('12345678', PhoneUtils::normalize_phone('012345678'));
    }

    public function testMessageUtilsClean(): void
    {
        $dirty = "Hello \xF0\x9F\x98\x80 world\xC2\xA0test";
        $clean = MessageUtils::clean_message($dirty);

        $this->assertIsString($clean);
        // Should not contain emoji
        $this->assertDoesNotMatchRegularExpression(
            '/[\x{1F600}-\x{1F64F}]/u',
            $clean,
            'clean_message should strip emoji'
        );
    }
}

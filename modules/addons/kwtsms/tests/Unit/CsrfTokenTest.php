<?php

/*
 * FILE:      tests/Unit/CsrfTokenTest.php
 * CREATED:   2026-11-08 08:22 +03
 * TASK:      whmcs-91-compat
 * AUTHOR:    M Alhassoun
 * WHAT:      Tests kwtsms_csrf_token(): WHMCS generate_token() first, $_SESSION['token'] as the fallback.
 * WHY:       WHMCS 9.1 no longer keeps $_SESSION['token'], so the admin AJAX calls failed with "Invalid token".
 * USAGE:     vendor/bin/phpunit --testsuite Unit
 * DEPENDS:   kwtsms.php; the generate_token() stub below stands in for WHMCS.
 */

declare(strict_types=1);

namespace {
    if (!function_exists('generate_token')) {
        /** Test stub for the WHMCS core function; returns what the test puts in the global. */
        function generate_token(string $type = 'form'): string
        {
            return (string) ($GLOBALS['kwt_test_generated_token'] ?? '');
        }
    }
}

namespace KwtSMS\WHMCS\Tests\Unit {

    use PHPUnit\Framework\TestCase;

    class CsrfTokenTest extends TestCase
    {
        protected function setUp(): void
        {
            require_once __DIR__ . '/../../kwtsms.php';
            unset($_SESSION['token'], $GLOBALS['kwt_test_generated_token']);
        }

        public function testWhmcs91TokenWithoutSessionKey(): void
        {
            $GLOBALS['kwt_test_generated_token'] = 'abc123';
            $this->assertSame('abc123', kwtsms_csrf_token());
        }

        public function testGeneratedTokenWinsOverSession(): void
        {
            $_SESSION['token'] = 'old';
            $GLOBALS['kwt_test_generated_token'] = 'new';
            $this->assertSame('new', kwtsms_csrf_token());
        }

        public function testFallsBackToSessionToken(): void
        {
            $_SESSION['token'] = 'sess456';
            $this->assertSame('sess456', kwtsms_csrf_token());
        }

        public function testEmptyWhenNoTokenAnywhere(): void
        {
            $this->assertSame('', kwtsms_csrf_token());
        }
    }
}

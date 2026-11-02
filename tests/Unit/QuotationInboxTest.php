<?php

namespace Tests\Unit;

use App\Support\QuotationInbox;
use PHPUnit\Framework\TestCase;

class QuotationInboxTest extends TestCase
{
    public function test_connection_failures_provide_actionable_guidance_without_raw_details(): void
    {
        $inbox = new QuotationInbox;
        foreach ([
            'Certificate failure: private-server' => 'SSL certificate',
            'AUTHENTICATIONFAILED private-user' => 'rejected the login',
            'Host not found: private-server' => 'hostname could not be found',
            'Connection timed out: private-server' => 'port 993',
            'Unknown failure: private-user' => 'Could not open the inbox',
        ] as $error => $expected) {
            $message = $inbox->connectionError([$error]);
            $this->assertStringContainsString($expected, $message);
            $this->assertStringNotContainsString('private-', $message);
        }
    }
}

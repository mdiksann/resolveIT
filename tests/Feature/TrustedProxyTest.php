<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_https_proxy_generates_secure_asset_urls_and_redirects(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Port' => '443'])
            ->get('http://resolveit-bhtz.onrender.com/')
            ->assertOk()
            ->assertSee('https://resolveit-bhtz.onrender.com/favicon.ico', false)
            ->assertDontSee('http://resolveit-bhtz.onrender.com/favicon.ico', false);

        $this->get('http://resolveit-bhtz.onrender.com/tickets')
            ->assertRedirect('https://resolveit-bhtz.onrender.com/login');
    }

    public function test_direct_http_requests_keep_http_asset_urls(): void
    {
        $this->get('http://localhost/')
            ->assertOk()
            ->assertSee('http://localhost/favicon.ico', false);
    }
}

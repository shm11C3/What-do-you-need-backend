<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    /**
     * Get the host patterns that should be trusted.
     *
     * @return array<int, string|null>
     */
    public function hosts()
    {
        return [
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }

    /**
     * このアプリケーションで信用するプロキシ
     *
     * @var array
     */
    protected $proxies = '*';

    /**
     * プロキシを検出するために使用するヘッダ
     *
     * @var string
     */
    protected $headers = Request::HEADER_X_FORWARDED_AWS_ELB;
}

<?php

use Illuminate\Support\Facades\Http;

function createPremiunAccess($data)
{
    $url = env('SERVICE_COURSE_URL') . 'api/my-courses/premium';

    try {
        $response = Http::post($url, $data);

        $data = $response->json();
        $data['http_code'] = $response->status();

        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'message' => $th->getMessage(),
            'http_code' => 500,
        ];
    }
}

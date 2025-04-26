<?php

function confirmDelivery($guid) {
    $url = "https://www.paynow.co.zw/ConfirmDelivery";

    $pollUrl = "https://www.paynow.co.zw/Interface/CheckPayment/?guid=" . urlencode($guid);

    $postData = [
        'pollurl' => $pollUrl
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 🔥 still allow redirects

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        return [
            'success' => false,
            'message' => curl_error($ch)
        ];
    }

    curl_close($ch);

    if ($httpCode == 200) {
        // ✅ Paynow usually responds with a success XML or text
        return [
            'success' => true,
            'message' => 'Delivery confirmed successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'HTTP error code: ' . $httpCode . '. Response: ' . $response
        ];
    }
}
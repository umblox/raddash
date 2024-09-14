<?php
function getVoucherPrefix($planName) {
    // Array prefix voucher berdasarkan nama plan
    $VOUCHER_PREFIX = [
        '1Hari' => '3k',
        '5k' => '5k',
        '7Hari' => '15k',
        '30Hari' => '60k'
    ];

    // Jika planName ditemukan, return prefix-nya; jika tidak, return default prefix
    return isset($VOUCHER_PREFIX[$planName]) ? $VOUCHER_PREFIX[$planName] : 'edit_di_config/prefix';
}
?>

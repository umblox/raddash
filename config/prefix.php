<?php
/*
*******************************************************************************************************************
* Warning!!!, Tidak untuk diperjual belikan!, Cukup pakai sendiri atau share kepada orang lain secara gratis
*******************************************************************************************************************
* Dibuat oleh Ikromul Umam https://t.me/arnetadotid
*******************************************************************************************************************
* © 2024 Arneta.ID By https://fb.me/umblox
*******************************************************************************************************************
*/

function getVoucherPrefix($planName) {
    // sesuaikan prefix voucher berdasarkan nama plan anda
    $VOUCHER_PREFIX = [
        '1Hari' => '3k',
        'Trial' => 'trial',
        '7Hari' => '15k',
        '30Hari' => '60k'
    ];

    // Untuk nama plan Trial harus sama persis dengan yang disini, dan jangan ubah prefix trial nya
    // Jika planName ditemukan, return prefix-nya; jika tidak, return default prefix
    return isset($VOUCHER_PREFIX[$planName]) ? $VOUCHER_PREFIX[$planName] : 'edit_di_config/prefix';
}
?>

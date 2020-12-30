<?php
/**
 * Created by PhpStorm.
 * Author: CtrL
 * Date: 2019-03-20
 * Time: 17:23
 */

return [
    'parentMerchantNo'  => env("YEEPAY_PARENT_MERCHANTNO", ""),
    'merchantNo'        => env("YEEPAY_MERCHANTNO", ""),

    'private_key'       => env("YEEPAY_PRI_KEY", ""),
    'public_key'        => env("YEEPAY_PUB_KEY", ""),

    'serverRoot'        => env("YEEPAY_SERVER_ROOT"),

    'notifyUrl'         => env("YEEPAY_NOTIFT_URL",""),
    'refundNotifyUrl'         => env("YEEPAY_REFUND_NOTIFT_URL",""),
];

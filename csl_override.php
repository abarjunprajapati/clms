<?php
/**
 * CSL Payment Override - auto-prepended before every PHP request
 * This runs BEFORE payment_csl.php is loaded, intercepting create_training_order.php
 * to provide correct CSL API implementation.
 */

// Only act when create_training_order.php is being requested
$script = $_SERVER['SCRIPT_FILENAME'] ?? '';
if (strpos($script, 'create_training_order') === false) {
    return; // Not our concern — skip
}

// Already handled by this override
if (defined('CSL_OVERRIDE_LOADED')) return;
define('CSL_OVERRIDE_LOADED', true);

// Completely handle the request ourselves — output JSON and exit
session_start();

// Include only what we need (NOT the broken payment_csl.php)
$base = dirname(dirname(dirname(__FILE__))); // project root
require_once $base . '/include/config.php';
require_once $base . '/include/payment_flow.php';
require_once $base . '/include/AuditLogger.php';

header('Content-Type: application/json; charset=utf-8');

function _csl_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function _csl_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
}

function _csl_token($secret, $ip) {
    $uuid = _csl_uuid();
    $ts   = round(microtime(true) * 1000);
    $msg  = "SFTCLS|$ip|$ts|$uuid";
    $sig  = base64_encode(hash_hmac('sha256', $msg, $secret, true));
    return " " . base64_encode("$msg|$sig") . " 0";
}

function _csl_call_api($conn, $request) {
    $url    = 'https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder';
    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
    if (empty($secret)) return ['ok' => false, 'msg' => 'CSL secret key not configured.'];

    $workers = clms_training_payment_workers($conn, $request['id']);
    $list    = [];
    $total   = 0;
    foreach ($workers as $w) {
        $aad = preg_replace('/\D/', '', $w['aadhaar'] ?? '');
        if (strlen($aad) !== 12) $aad = str_pad($aad, 12, '0', STR_PAD_LEFT);
        $amt   = (int)($w['safety_fee'] ?? 0);
        $total += $amt;
        $list[] = ["aadharno" => $aad, "attemptno" => "1", "amount" => (string)$amt];
    }

    $vendor = '0000000000';
    $cRow   = db_single($conn, "SELECT vendor_code,sap_code,application_no FROM contractors WHERE id=? LIMIT 1", 'i', [(int)$request['contractor_id']]);
    if ($cRow) $vendor = $cRow['vendor_code'] ?: ($cRow['sap_code'] ?: $cRow['application_no']);

    $ip = '52.172.135.84';
    $ipDb = trim((string)clms_payment_setting($conn, 'csl_source_ip', ''));
    if ($ipDb && filter_var($ipDb, FILTER_VALIDATE_IP)) $ip = $ipDb;

    $token   = _csl_token($secret, $ip);
    $payload = json_encode([
        "source_IP"=>"$ip","source_Type"=>"WEB","app_ID"=>"CLMS_SFTCLS",
        "remit_Req_ID"=>$vendor,"total_Amount"=>(string)$total,"paymode"=>"RAZORPAY",
        "ref_Trans_List"=>$list
    ]);

    // Log
    @file_put_contents($base.'/include/csl_debug.log',
        date('[Y-m-d H:i:s]')." OVERRIDE ACTIVE\nIP=$ip\nPAYLOAD=$payload\n", FILE_APPEND);

    $ch = curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload,
        CURLOPT_HTTPHEADER=>["Content-Type: application/json","token: $token"],
        CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>15
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    @file_put_contents($base.'/include/csl_debug.log',
        "HTTP=$code CURL_ERR=".($err?:'none')."\nRESP=$resp\n".str_repeat('=',60)."\n", FILE_APPEND);

    if ($err) return ['ok'=>false,'msg'=>"cURL: $err"];

    if ($code >= 200 && $code < 300) {
        // CSL returns HTML with inline JS setup for Razorpay. Parse it!
        if (strpos($resp, 'checkout.js') !== false || strpos($resp, 'startPayment') !== false) {
            $oid = '';
            $rkey = '';
            if (preg_match("/order_id:\s*'([^']+)'/", $resp, $m)) {
                $oid = $m[1];
            }
            if (preg_match("/key:\s*'([^']+)'/", $resp, $m)) {
                $rkey = $m[1];
            }
            
            if ($oid) {
                db_execute($conn, "UPDATE training_payment_requests SET gateway_order_id=?,status='gateway_created',updated_at=NOW() WHERE id=?", 'si', [$oid, $request['id']]);
                return ['ok' => true, 'order_id' => $oid, 'key_id' => $rkey];
            }
        }

        $dec = json_decode($resp, true);
        $get = function($a,$k){ if(!is_array($a))return null; foreach($a as $key=>$v){if(trim($key)===$k)return trim($v);}return null; };
        $st  = $get($dec, 'status');
        if ($st && strtoupper($st) === 'SUCCESS') {
            $oid = $get($dec, 'order_id');
            db_execute($conn,"UPDATE training_payment_requests SET gateway_order_id=?,status='gateway_created',updated_at=NOW() WHERE id=?",'si',[$oid,$request['id']]);
            return ['ok'=>true,'order_id'=>$oid];
        }
        $emsg = $get($dec,'message');
        if (!$emsg) {
            $emsg = strpos($resp,'IP not in trusted host list')!==false
                ? 'CSL Firewall: Whitelist IP 52.172.135.84 on CSL server'
                : 'Raw: '.substr(trim(strip_tags($resp)),0,200);
        }
        return ['ok'=>false,'msg'=>"API Error: $emsg"];
    }
    return ['ok'=>false,'msg'=>"HTTP $code: ".substr($resp,0,200)];
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) _csl_json(['success'=>false,'message'=>'Invalid request payload.'], 400);

    $token   = trim((string)($input['token'] ?? ''));
    $request = $token ? clms_get_training_payment_request($conn, $token) : null;
    if (!$request) _csl_json(['success'=>false,'message'=>'Payment request not found.'], 404);

    if (in_array(strtolower($request['status']), ['paid','verified']))
        _csl_json(['success'=>false,'message'=>'Payment already completed.'], 400);

    $provider  = clms_payment_setting($conn, 'payment_gateway_provider', 'demo_qr');
    $keyId     = trim((string)clms_payment_setting($conn, 'payment_gateway_key_id', ''));
    $keySecret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));

    if (!$keyId || !$keySecret) _csl_json(['success'=>false,'message'=>'Payment credentials not configured.'], 400);

    if ($provider === 'csl_payment') {
        $r = _csl_call_api($conn, $request);
        if (!$r['ok']) _csl_json(['success'=>false,'message'=>$r['msg']], 400);

        $contractor = clms_get_contractor_user_for_payment($conn, (int)$request['contractor_id']);
        AuditLogger::log($conn,'CSL_ORDER_CREATED','payment','',['ref'=>$request['payment_ref'],'order'=>$r['order_id']],'CSL order created.');
        
        $finalKey = !empty($r['key_id']) ? $r['key_id'] : $keyId;

        _csl_json([
            'success'=>true,'message'=>'CSL order created.','provider'=>'razorpay',
            'key_id'=>$finalKey,'gateway_order_id'=>$r['order_id'],
            'amount'=>$request['total_amount'],'currency'=>'INR','token'=>$token,
            'contractor_name'=>$contractor['contractor_name']??'Contractor',
            'contractor_email'=>$contractor['email']??'',
            'contractor_phone'=>$contractor['mobile']??''
        ]);

    } elseif ($provider === 'razorpay') {
        $ch = curl_init("https://api.razorpay.com/v1/orders");
        curl_setopt_array($ch,[CURLOPT_USERPWD=>"$keyId:$keySecret",CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(['amount'=>round((float)$request['total_amount']*100),'currency'=>'INR','receipt'=>$request['payment_ref']]),
            CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_SSL_VERIFYPEER=>false,CURLOPT_TIMEOUT=>30]);
        $resp=$curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        $rzp=json_decode($resp,true);
        if($code!==200||empty($rzp['id'])) _csl_json(['success'=>false,'message'=>$rzp['error']['description']??'Razorpay failed.'],400);
        db_execute($conn,"UPDATE training_payment_requests SET status='gateway_created',gateway_provider=?,gateway_order_id=?,updated_at=NOW() WHERE id=?",'ssi',[$provider,$rzp['id'],(int)$request['id']]);
        $contractor=clms_get_contractor_user_for_payment($conn,(int)$request['contractor_id']);
        _csl_json(['success'=>true,'provider'=>'razorpay','key_id'=>$keyId,'gateway_order_id'=>$rzp['id'],'amount'=>$request['total_amount'],'currency'=>'INR','token'=>$token,'contractor_name'=>$contractor['contractor_name']??'Contractor','contractor_email'=>$contractor['email']??'','contractor_phone'=>$contractor['mobile']??'']);

    } else {
        $oid='LOCAL-'.$request['payment_ref'];
        db_execute($conn,"UPDATE training_payment_requests SET status='gateway_created',gateway_provider=?,gateway_order_id=?,updated_at=NOW() WHERE id=?",'ssi',[$provider,$oid,(int)$request['id']]);
        $out=['success'=>true,'provider'=>$provider,'order_id'=>$oid,'amount'=>$request['total_amount'],'currency'=>$request['currency']];
        if($provider==='demo_qr'){$out['checkout_mode']='demo_qr';$out['demo']=clms_demo_payment_details($conn,$request);}
        _csl_json($out);
    }

} catch (Throwable $e) {
    error_log('[CSL_OVERRIDE] '.$e->getMessage().' @'.$e->getFile().':'.$e->getLine());
    _csl_json(['success'=>false,'message'=>'Order failed: '.$e->getMessage()], 500);
}

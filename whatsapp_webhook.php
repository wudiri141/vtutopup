<?php
require_once "whatsapp_send.php";
require_once "whatsapp_helpers.php";
require_once "whatsapp_actions.php";

define("VERIFY_TOKEN", "vtutopupx_verify");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($mode === 'subscribe' && $token === VERIFY_TOKEN) {
        echo $challenge;
        exit();
    }

    http_response_code(403);
    echo "Verification failed";
    exit();
}

$inputRaw = file_get_contents("php://input");
file_put_contents(__DIR__ . "/whatsapp_log.txt", $inputRaw . PHP_EOL, FILE_APPEND);

$input = json_decode($inputRaw, true);

if (!isset($input['entry'][0]['changes'][0]['value']['messages'][0])) {
    echo "EVENT_RECEIVED";
    exit();
}

$messageObj = $input['entry'][0]['changes'][0]['value']['messages'][0];
$from = $messageObj['from'] ?? '';
$text = trim($messageObj['text']['body'] ?? '');
$lower = strtolower(trim($text));

$localPhone = normalizePhone($from);
$user = getUserByPhone($localPhone);
$session = getWaSession($localPhone);

file_put_contents(
    __DIR__ . "/wa_debug.txt",
    date('Y-m-d H:i:s') .
    " | from={$from}" .
    " | localPhone={$localPhone}" .
    " | text={$text}" .
    " | lower={$lower}" .
    " | session=" . json_encode($session) .
    PHP_EOL,
    FILE_APPEND
);

if (!$user) {
    sendWhatsAppMessage(
        $from,
        "Welcome to VTU TOPUP X.\n\nYour number is not linked to an account yet.\nRegister here:\nhttps://vtutopupx.com.ng/register.php"
    );
    echo "EVENT_RECEIVED";
    exit();
}

/*
|--------------------------------------------------------------------------
| Global commands
|--------------------------------------------------------------------------
*/
if (in_array($lower, ['menu','hi','hello','start'])) {
    clearWaSession($localPhone);
    sendWhatsAppMessage(
        $from,
        "Welcome {$user['fullname']} to VTU TOPUP X\n\n1. Buy Airtime\n2. Buy Data\n3. Check Wallet\n4. Support"
    );
    echo "EVENT_RECEIVED";
    exit();
}

if (in_array($lower, ['cancel','reset'])) {
    clearWaSession($localPhone);
    sendWhatsAppMessage($from, "Session cleared.\nSend MENU to start again.");
    echo "EVENT_RECEIVED";
    exit();
}

if ($lower === '3') {
    $wallet = number_format(getWalletBalance((int)$user['id']), 2);
    sendWhatsAppMessage($from, "Your wallet balance is ₦{$wallet}");
    echo "EVENT_RECEIVED";
    exit();
}

if ($lower === '4') {
    sendWhatsAppMessage($from, "Support\nWhatsApp: 2349161044495\nEmail: support@vtutopupx.com.ng");
    echo "EVENT_RECEIVED";
    exit();
}

/*
|--------------------------------------------------------------------------
| Data session flow
|--------------------------------------------------------------------------
*/
if ($session && ($session['action'] ?? '') === 'data') {
    $data = json_decode($session['data'] ?? '{}', true);
    if (!is_array($data)) {
        $data = [];
    }

    if (($session['step'] ?? '') === 'network') {
        $map = [
            '1' => 'MTN',
            '2' => 'AIRTEL',
            '3' => 'GLO',
            '4' => '9MOBILE'
        ];

        if (!isset($map[$lower])) {
            sendWhatsAppMessage($from, "Invalid option.\nReply with:\n1 for MTN\n2 for Airtel\n3 for Glo\n4 for 9mobile");
            echo "EVENT_RECEIVED";
            exit();
        }

        $network = $map[$lower];
        $plans = getDataPlansByNetwork($network);

        if (empty($plans)) {
            clearWaSession($localPhone);
            sendWhatsAppMessage($from, "No active data plans found for {$network} right now.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $data['network'] = $network;
        $data['plans'] = $plans;

        saveWaSession($localPhone, 'data', 'plan', $data);

        sendWhatsAppMessage($from, formatDataPlansForWhatsApp($plans));
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'plan') {
        if (!preg_match('/^\d+$/', $text)) {
            sendWhatsAppMessage($from, "Invalid option. Reply with the number of the plan you want.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $choice = (int)$text;
        $plans = $data['plans'] ?? [];

        if ($choice < 1 || $choice > count($plans)) {
            sendWhatsAppMessage($from, "Invalid plan option. Please choose a valid number from the list.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $selectedPlan = $plans[$choice - 1];
        $data['plan'] = $selectedPlan['data_plan_id'];

        saveWaSession($localPhone, 'data', 'phone', $data);

        sendWhatsAppMessage($from, "Enter phone number");
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'phone') {
        $phone = preg_replace('/\D+/', '', $text);

        if (strlen($phone) !== 11) {
            sendWhatsAppMessage($from, "Invalid phone number. Enter 11-digit number.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $data['phone'] = $phone;
        saveWaSession($localPhone, 'data', 'pin', $data);

        sendWhatsAppMessage($from, "Enter your 4-digit transaction PIN");
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'pin') {
        $pin = trim($text);

        if (!preg_match('/^\d{4}$/', $pin)) {
            sendWhatsAppMessage($from, "PIN must be 4 digits. Enter your 4-digit transaction PIN.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $network = $data['network'] ?? '';
        $phone = $data['phone'] ?? '';
        $planId = $data['plan'] ?? '';

        $result = processWhatsAppData((int)$user['id'], $network, $phone, $planId, $pin);

        clearWaSession($localPhone);
        sendWhatsAppMessage($from, $result['message']);

        echo "EVENT_RECEIVED";
        exit();
    }
}
/*
|--------------------------------------------------------------------------
| Airtime session flow
|--------------------------------------------------------------------------
*/
if ($session && ($session['action'] ?? '') === 'airtime') {
    $data = json_decode($session['data'] ?? '{}', true);
    if (!is_array($data)) {
        $data = [];
    }

    if (($session['step'] ?? '') === 'network') {
        $map = [
            '1' => 'MTN',
            '2' => 'AIRTEL',
            '3' => 'GLO',
            '4' => '9MOBILE'
        ];

        if (!isset($map[$lower])) {
            sendWhatsAppMessage($from, "Invalid option.\nReply with:\n1 for MTN\n2 for Airtel\n3 for Glo\n4 for 9mobile");
            echo "EVENT_RECEIVED";
            exit();
        }

        $data['network'] = $map[$lower];
        saveWaSession($localPhone, 'airtime', 'phone', $data);

        sendWhatsAppMessage($from, "Enter phone number");
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'phone') {
        $phone = preg_replace('/\D+/', '', $text);

        if (strlen($phone) !== 11) {
            sendWhatsAppMessage($from, "Invalid phone number. Enter 11-digit number.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $data['phone'] = $phone;
        saveWaSession($localPhone, 'airtime', 'amount', $data);

        sendWhatsAppMessage($from, "Enter amount");
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'amount') {
        $amount = (float)$text;

        if ($amount < 50) {
            sendWhatsAppMessage($from, "Minimum airtime amount is ₦50. Enter a valid amount.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $data['amount'] = $amount;
        saveWaSession($localPhone, 'airtime', 'pin', $data);

        sendWhatsAppMessage($from, "Enter your 4-digit transaction PIN");
        echo "EVENT_RECEIVED";
        exit();
    }

    if (($session['step'] ?? '') === 'pin') {
        $pin = trim($text);

        if (!preg_match('/^\d{4}$/', $pin)) {
            sendWhatsAppMessage($from, "PIN must be 4 digits. Enter your 4-digit transaction PIN.");
            echo "EVENT_RECEIVED";
            exit();
        }

        $network = $data['network'] ?? '';
        $phone = $data['phone'] ?? '';
        $amount = $data['amount'] ?? 0;

        $result = processWhatsAppAirtime((int)$user['id'], $network, $phone, $amount, $pin);

        clearWaSession($localPhone);
        sendWhatsAppMessage($from, $result['message']);

        echo "EVENT_RECEIVED";
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| Start Airtime flow
|--------------------------------------------------------------------------
*/
if ($lower === '1') {
    clearWaSession($localPhone);

    $saved = saveWaSession($localPhone, 'airtime', 'network', []);

    file_put_contents(
        __DIR__ . "/wa_debug.txt",
        date('Y-m-d H:i:s') . " | start airtime | saved=" . ($saved ? '1' : '0') . PHP_EOL,
        FILE_APPEND
    );

    if (!$saved) {
        sendWhatsAppMessage($from, "Could not start airtime session. Please try again.");
        echo "EVENT_RECEIVED";
        exit();
    }

    sendWhatsAppMessage(
        $from,
        "Select Network\n1. MTN\n2. Airtel\n3. Glo\n4. 9mobile"
    );
    echo "EVENT_RECEIVED";
    exit();
}

/*
|--------------------------------------------------------------------------
| Start Data flow
|--------------------------------------------------------------------------
*/
if ($lower === '2') {
    clearWaSession($localPhone);

    $saved = saveWaSession($localPhone, 'data', 'network', []);

    file_put_contents(
        __DIR__ . "/wa_debug.txt",
        date('Y-m-d H:i:s') . " | start data | saved=" . ($saved ? '1' : '0') . PHP_EOL,
        FILE_APPEND
    );

    if (!$saved) {
        sendWhatsAppMessage($from, "Could not start data session. Please try again.");
        echo "EVENT_RECEIVED";
        exit();
    }

    sendWhatsAppMessage(
        $from,
        "Select Network\n1. MTN\n2. Airtel\n3. Glo\n4. 9mobile"
    );
    echo "EVENT_RECEIVED";
    exit();
}

sendWhatsAppMessage($from, "Sorry, I did not understand that.\nSend MENU to continue.");
echo "EVENT_RECEIVED";
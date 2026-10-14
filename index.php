<?php
/**
 * فاکتورساز فارسی — نسخه کامل
 * تمام عنوان‌ها، ردیف‌ها و مبالغ به‌صورت زنده قابل ویرایش هستند.
 * داده‌ها در invoices.json کنار همین فایل ذخیره می‌شوند.
 */
$dataFile = __DIR__ . '/invoices.json';
function readAll($dataFile){
    $all = [];
    if (is_readable($dataFile)) {
        $existing = json_decode(file_get_contents($dataFile), true);
        if (is_array($existing)) $all = $existing;
    }
    return $all;
}
// تبدیل تاریخ میلادی به شمسی (فقط برای گرفتن سال جاری، جهت شماره‌گذاری فاکتور)
function gregorianToJalaliYear($gy, $gm, $gd){
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv(($gy2 + 3), 4) - intdiv(($gy2 + 99), 100) + intdiv(($gy2 + 399), 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
    }
    return $jy;
}
// ------- ذخیره فاکتور -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    header('Content-Type: application/json; charset=utf-8');
    $payload = $_POST['payload'] ?? '';
    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
        echo json_encode(['ok' => false, 'error' => 'داده نامعتبر است']);
        exit;
    }
    $all = readAll($dataFile);
    // شمارنده شماره فاکتور به‌صورت «سال شمسی-ردیف» مثل 1404-1001
    $jy = gregorianToJalaliYear((int)date('Y'), (int)date('n'), (int)date('j'));
    $yearKey = (string)$jy;
    $meta = $all['_meta'] ?? ['seq' => []];
    if (!isset($meta['seq'][$yearKey])) $meta['seq'][$yearKey] = 1000;
    if (empty($decoded['invoiceNumber'])) {
        $meta['seq'][$yearKey] += 1;
        $decoded['invoiceNumber'] = $yearKey . '-' . $meta['seq'][$yearKey];
    }
    $all['_meta'] = $meta;
    $decoded['id'] = $decoded['id'] ?? uniqid('inv_');
    $decoded['saved_at'] = date('c');
    $all[$decoded['id']] = $decoded;
    $ok = @file_put_contents($dataFile, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['ok' => (bool)$ok, 'id' => $decoded['id'], 'invoiceNumber' => $decoded['invoiceNumber']]);
    exit;
}
// ------- حذف فاکتور ذخیره‌شده -------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    header('Content-Type: application/json; charset=utf-8');
    $id = $_POST['id'] ?? '';
    $all = readAll($dataFile);
    if (isset($all[$id])) {
        unset($all[$id]);
        $ok = @file_put_contents($dataFile, json_encode($all, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        echo json_encode(['ok' => (bool)$ok]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'یافت نشد']);
    }
    exit;
}
// ------- لیست فاکتورهای ذخیره‌شده -------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $all = readAll($dataFile);
    unset($all['_meta']);
    echo json_encode(array_values($all));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>فاکتورساز</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#eef3fb;
    --panel:#ffffff;
    --ink:#1f2937;
    --muted:#8a94a6;
    --line:#e6ebf3;
    --accent:#1f5fd1;
    --accent-dark:#153f8c;
    --teal:#12b0a0;
    --danger:#e2503a;
    --amber:#c9860a;
    --radius:14px;
    --shadow:0 8px 24px rgba(31,53,109,.08);
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    font-family:'Vazirmatn',sans-serif;
    background:var(--bg);
    color:var(--ink);
    padding:32px 16px 80px;
    transition:background .2s,color .2s;
  }
  body.dark{
    --bg:#0f1420;
    --panel:#1a2233;
    --ink:#e7ecf5;
    --muted:#8792a8;
    --line:#2a3348;
    --accent:#5b8dff;
    --accent-dark:#8fb0ff;
    --shadow:0 8px 24px rgba(0,0,0,.35);
  }
  body.dark .logo-box,body.dark .stamp-box{background:#141b2a;}
  body.dark .amount-input,body.dark .paid-input-big,body.dark .pid-input,body.dark .tax-pct{background:#141b2a;}
  .wrap{max-width:760px;margin:0 auto;}
  .toolbar{
    display:flex;justify-content:space-between;align-items:center;
    margin-bottom:20px;flex-wrap:wrap;gap:10px;
  }
  .toolbar-left{display:flex;align-items:center;gap:10px;}
  .toolbar h1{font-size:19px;font-weight:700;margin:0;color:var(--accent-dark);}
  .btns{display:flex;gap:8px;flex-wrap:wrap;}
  button{
    font-family:inherit;cursor:pointer;border:none;border-radius:10px;
    padding:10px 16px;font-size:14px;font-weight:600;transition:.15s;
  }
  .btn-primary{background:var(--accent);color:#fff;}
  .btn-primary:hover{background:var(--accent-dark);}
  .btn-ghost{background:var(--panel);color:var(--accent-dark);border:1px solid var(--line);}
  .btn-ghost:hover{background:#f3f6fc;}
  .btn-danger{background:var(--panel);color:var(--danger);border:1px solid #f3d4cc;}
  .btn-danger:hover{background:#fdecea;}
  .btn-sm{padding:6px 12px;font-size:12.5px;}
  .icon-btn{
    width:38px;height:38px;padding:0;border-radius:10px;background:var(--panel);
    border:1px solid var(--line);display:flex;align-items:center;justify-content:center;font-size:16px;
  }
  .icon-btn:hover{background:#f3f6fc;}
  /* -------- letterhead -------- */
  .letterhead{
    position:relative;display:flex;align-items:center;gap:16px;background:var(--panel);
    border-radius:var(--radius);box-shadow:var(--shadow);padding:22px 20px 18px;
    margin-bottom:12px;overflow:hidden;
  }
  .letterhead::before{
    content:'';position:absolute;top:0;left:0;right:0;height:5px;
    background:linear-gradient(90deg,var(--accent),var(--teal));
  }
  .logo-box{
    width:76px;height:76px;flex:0 0 76px;border-radius:12px;
    background:#f7fafe;border:1.5px dashed #c7d4ea;display:flex;
    align-items:center;justify-content:center;cursor:pointer;overflow:hidden;
    position:relative;color:var(--muted);font-size:11px;text-align:center;line-height:1.4;
  }
  .logo-box:hover{border-color:var(--accent);background:#f0f5fd;}
  .logo-box img{width:100%;height:100%;object-fit:contain;}
  .logo-remove{
    position:absolute;top:2px;left:2px;background:rgba(31,41,55,.65);color:#fff;
    border-radius:6px;width:18px;height:18px;font-size:11px;line-height:18px;
    padding:0;display:none;
  }
  .logo-box.has-logo .logo-remove{display:block;}
  .logo-box.has-logo{border-style:solid;cursor:default;}
  .letterhead-fields{flex:1;min-width:0;display:flex;flex-direction:column;gap:4px;}
  .company-name-input{
    border:1px solid transparent;background:transparent;font-family:inherit;
    font-weight:800;font-size:17px;color:var(--accent-dark);padding:4px 4px;border-radius:6px;
    width:100%;
  }
  .company-name-input:focus{border-color:var(--accent);background:#f7fafe;outline:none;}
  .letterhead-meta{display:flex;flex-wrap:wrap;gap:4px 18px;}
  .meta-field{display:flex;align-items:center;gap:6px;}
  .meta-field .meta-icon{font-size:13px;color:var(--teal);}
  .meta-field input{
    border:1px solid transparent;background:transparent;font-family:inherit;
    font-size:13px;color:var(--muted);padding:3px 4px;border-radius:6px;min-width:120px;
  }
  .meta-field input:focus{border-color:var(--accent);background:#f7fafe;outline:none;color:var(--ink);}
  /* -------- invoice number + status row -------- */
  .status-row{
    display:flex;justify-content:space-between;align-items:center;gap:10px;
    background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);
    padding:12px 18px;margin-bottom:12px;flex-wrap:wrap;
  }
  .invoice-number{font-size:14px;color:var(--muted);}
  .invoice-number b{color:var(--accent-dark);font-size:16px;font-variant-numeric:tabular-nums;}
  .draft-watermark{
    position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-28deg);
    font-size:72px;font-weight:800;color:rgba(180,60,60,.12);
    pointer-events:none;z-index:5;white-space:nowrap;display:none;user-select:none;
    letter-spacing:4px;
  }
  .draft-watermark.show{display:block;}
  body.dark .draft-watermark{color:rgba(255,255,255,.07);}
  .currency-select,.template-select{
    border:1px solid var(--line);background:var(--panel);color:var(--muted);
    border-radius:8px;padding:5px 8px;font-family:inherit;font-size:12.5px;cursor:pointer;
  }
  .template-row{margin-bottom:14px;}
  .template-select{width:100%;padding:12px 14px;border:1.5px dashed #c7d4ea;border-radius:var(--radius);
    color:var(--accent-dark);font-weight:600;background:var(--panel);}
  .template-select:hover{background:#f3f6fc;border-color:var(--accent);}
  .status-wrap{display:flex;align-items:center;gap:8px;}
  .status-select{
    border:1px solid var(--line);background:var(--panel);color:var(--muted);
    border-radius:8px;padding:5px 8px;font-family:inherit;font-size:12.5px;
  }
  .status-badge{
    padding:5px 14px;border-radius:999px;font-size:12.5px;font-weight:700;
    display:inline-block;
  }
  .status-badge.paid{background:#e4f7ee;color:#15824b;}
  .status-badge.partial{background:#fff3e0;color:#9a5b00;}
  .status-badge.pending{background:#eef1f6;color:#5b667a;}
  .status-badge.overdue{background:#fdecea;color:#b1281a;}
  .status-badge.canceled{background:#f1f1f4;color:#6b7280;text-decoration:line-through;}
  body.dark .status-badge.paid{background:#0d2f20;color:#57d998;}
  body.dark .status-badge.partial{background:#332405;color:#f0b429;}
  body.dark .status-badge.pending{background:#1f2636;color:#aab4c8;}
  body.dark .status-badge.overdue{background:#3a1512;color:#ff8a7a;}
  .lock-banner{
    display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;
    background:#fff3e0;color:#9a5b00;border-radius:12px;padding:11px 16px;
    margin-bottom:12px;font-size:13px;
  }
  body.dark .lock-banner{background:#332405;color:#f0b429;}
  /* -------- customer card -------- */
  .customer-card{
    background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);
    padding:16px 20px;margin-bottom:12px;
  }
  .customer-title{font-size:12.5px;color:var(--muted);margin-bottom:8px;}
  .customer-name-input{
    border:1px solid transparent;background:transparent;font-family:inherit;
    font-weight:700;font-size:14.5px;color:var(--ink);padding:4px;border-radius:6px;width:100%;margin-bottom:4px;
  }
  .customer-name-input:focus{border-color:var(--accent);background:#f7fafe;outline:none;}
  /* -------- dates -------- */
  .dates-row{
    display:flex;background:#f7fafe;border-radius:var(--radius);
    padding:16px 18px;margin-bottom:18px;box-shadow:var(--shadow);
    gap:8px;
  }
  body.dark .dates-row{background:#141b2a;}
  .date-box{flex:1;text-align:center;position:relative;}
  .date-box:not(:last-child)::after{
    content:'';position:absolute;top:6px;bottom:6px;left:0;width:2px;
    background:var(--teal);border-radius:2px;
  }
  .date-box label{display:block;font-size:12.5px;color:var(--muted);margin-bottom:6px;}
  .date-box input{
    border:none;background:transparent;text-align:center;font-size:14px;
    font-weight:600;color:var(--ink);width:100%;font-family:inherit;font-variant-numeric:tabular-nums;
  }
  .date-box input:focus{outline:1px dashed var(--accent);border-radius:6px;}
  /* -------- item cards -------- */
  .card{
    background:var(--panel);border-radius:var(--radius);box-shadow:var(--shadow);
    margin-bottom:16px;overflow:hidden;
  }
  .item-card.dragging{opacity:.4;}
  .subtotal-marker{
    display:flex;justify-content:space-between;align-items:center;
    padding:8px 18px;margin:-8px 0 16px;color:var(--muted);font-size:12.5px;
    border-top:1px dashed var(--line);border-bottom:1px dashed var(--line);background:#f7fafe;
    border-radius:8px;font-weight:600;
  }
  body.dark .subtotal-marker{background:#141b2a;}
  .subtotal-marker span:last-child{font-variant-numeric:tabular-nums;font-weight:700;color:var(--ink);}
  .item-head{
    display:flex;align-items:center;justify-content:space-between;
    padding:16px 18px;gap:10px;border-bottom:1px solid var(--line);
  }
  .item-head-left{display:flex;align-items:center;gap:10px;flex:1;min-width:0;}
  .drag-handle{
    cursor:grab;color:var(--muted);font-size:15px;flex:0 0 auto;padding:2px 4px;
    user-select:none;
  }
  .drag-handle:active{cursor:grabbing;}
  .amount-input-wrap{display:flex;align-items:center;gap:6px;white-space:nowrap;}
  .amount-input{
    border:1px solid transparent;background:#f7fafe;border-radius:8px;
    padding:6px 8px;font-family:inherit;font-weight:700;font-size:14px;
    width:120px;text-align:left;color:var(--ink);font-variant-numeric:tabular-nums;
  }
  .amount-input:focus{border-color:var(--accent);outline:none;background:#fff;}
  .unit{color:var(--muted);font-size:13px;}
  .title-input{
    border:1px solid transparent;background:transparent;font-family:inherit;
    font-weight:700;font-size:14.5px;flex:1;min-width:0;color:var(--ink);
    padding:6px 4px;border-radius:6px;
  }
  .title-input:focus{border-color:var(--accent);background:#f7fafe;outline:none;}
  .drag-remove{
    background:transparent;color:var(--danger);font-size:16px;line-height:1;
    padding:4px 8px;border-radius:8px;
  }
  .drag-remove:hover{background:#fdecea;}
  .item-body{padding:14px 18px 16px;}
  .subtitle-input{
    width:100%;border:1px solid transparent;background:transparent;font-family:inherit;
    font-size:13.5px;color:var(--muted);margin-bottom:10px;padding:4px;border-radius:6px;
  }
  .subtitle-input:focus{border-color:var(--accent);background:#f7fafe;outline:none;}
  .spec-row{
    display:flex;justify-content:space-between;gap:8px;align-items:center;
    padding:5px 0;font-size:13.5px;
  }
  .spec-icon{flex:0 0 auto;font-size:13px;}
  .spec-row input{
    border:1px solid transparent;background:transparent;font-family:inherit;
    font-size:13.5px;color:var(--ink);padding:4px 6px;border-radius:6px;
  }
  .spec-row input.spec-key{flex:1;text-align:right;color:var(--muted);}
  .spec-row input.spec-val{flex:1;text-align:left;font-weight:600;font-variant-numeric:tabular-nums;}
  .spec-row input:focus{border-color:var(--accent);background:#f7fafe;outline:none;}
  .spec-remove{background:transparent;color:var(--muted);padding:2px 6px;font-size:13px;}
  .spec-remove:hover{color:var(--danger);}
  .add-spec-btn{
    margin-top:6px;background:transparent;color:var(--accent);font-size:12.5px;
    padding:4px 2px;font-weight:600;
  }
  .add-spec-btn:hover{text-decoration:underline;}
  .add-item-btn{
    width:100%;background:var(--panel);border:1.5px dashed #c7d4ea;color:var(--accent-dark);
    border-radius:var(--radius);padding:14px;font-weight:700;margin-bottom:18px;
  }
  .add-item-btn:hover{background:#f3f6fc;border-color:var(--accent);}
  /* -------- summary -------- */
  .summary-card{padding:20px 22px 22px;}
  .divider-label{
    display:flex;align-items:center;gap:12px;color:var(--muted);font-size:12.5px;
    margin-bottom:14px;
  }
  .divider-label::before,.divider-label::after{content:'';flex:1;height:1px;background:var(--line);}
  .calc-table{margin-bottom:6px;}
  .calc-row{
    display:flex;justify-content:space-between;align-items:center;
    padding:8px 2px;font-size:13.5px;border-bottom:1px solid var(--line);
  }
  .calc-row:last-child{border-bottom:none;}
  .calc-label{color:var(--ink);display:flex;align-items:center;gap:6px;}
  .calc-val{font-weight:700;font-variant-numeric:tabular-nums;}
  .calc-val.muted{color:var(--muted);font-weight:600;}
  .tax-pct{
    width:44px;border:1px solid transparent;background:#f7fafe;border-radius:6px;
    text-align:center;font-family:inherit;font-weight:600;padding:3px 4px;font-size:12.5px;
  }
  .tax-pct:focus{border-color:var(--accent);outline:none;background:#fff;}
  .final-total-box{
    background:#eef4ff;border-radius:12px;padding:14px 16px;margin:12px 0;
  }
  body.dark .final-total-box{background:#152040;}
  .final-total-top{display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:6px;}
  .final-total-label{color:var(--accent-dark);font-weight:800;font-size:14.5px;}
  .final-total-value{color:var(--accent-dark);font-weight:800;font-size:18px;font-variant-numeric:tabular-nums;}
  .final-total-words{
    width:100%;text-align:right;color:var(--muted);font-size:12px;margin-top:6px;line-height:1.7;
  }
  .payment-box{
    background:#f7fafe;border-radius:12px;padding:14px 16px;margin-bottom:10px;
    display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
  }
  body.dark .payment-box{background:#141b2a;}
  .payment-box-main{display:flex;align-items:baseline;gap:8px;}
  .paid-label-big{font-weight:800;font-size:15px;color:var(--ink);}
  .paid-input-big{
    border:1px solid transparent;background:#fff;border-radius:8px;
    padding:5px 8px;font-family:inherit;font-weight:800;font-size:15px;
    width:130px;text-align:left;color:var(--accent-dark);font-variant-numeric:tabular-nums;
  }
  body.dark .paid-input-big{background:#0f1420;}
  .paid-input-big:focus{border-color:var(--accent);outline:none;}
  .unit-big{font-weight:700;font-size:13px;color:var(--muted);}
  .payment-box-id{display:flex;align-items:center;gap:6px;}
  .pid-label{color:var(--muted);font-size:12px;}
  .pid-input{
    border:1px solid transparent;background:#fff;border-radius:8px;
    padding:5px 8px;font-family:inherit;font-weight:600;font-size:12.5px;
    width:120px;text-align:left;
  }
  body.dark .pid-input{background:#0f1420;}
  .pid-input:focus{border-color:var(--accent);outline:none;}
  .qr-toggle-btn{
    background:transparent;color:var(--accent);font-size:12.5px;font-weight:600;
    padding:4px 2px;margin-bottom:4px;
  }
  .qr-toggle-btn:hover{text-decoration:underline;}
  .qr-row{
    display:flex;justify-content:space-between;align-items:flex-start;gap:12px;
    padding-top:8px;border-top:1px dashed var(--line);
  }
  .qr-row.collapsed{display:none;}
  .qr-row .label{flex-wrap:wrap;color:var(--ink);}
  .qr-row input{
    border:1px solid var(--line);background:#f7fafe;border-radius:8px;
    padding:5px 8px;font-family:inherit;font-size:12.5px;width:220px;text-align:left;margin-top:4px;
  }
  .qr-row input:focus{border-color:var(--accent);outline:none;background:#fff;}
  .qr-box{width:64px;height:64px;flex:0 0 auto;}
  .qr-box img,.qr-box canvas{width:100%;height:100%;}
  /* -------- stamp/signature -------- */
  .stamp-card{
    display:flex;align-items:center;gap:14px;padding:16px 20px;
  }
  .stamp-box{
    width:90px;height:90px;flex:0 0 90px;border-radius:12px;
    background:#f7fafe;border:1.5px dashed #c7d4ea;display:flex;
    align-items:center;justify-content:center;cursor:pointer;overflow:hidden;
    position:relative;color:var(--muted);font-size:11px;text-align:center;line-height:1.4;
  }
  .stamp-box:hover{border-color:var(--accent);background:#f0f5fd;}
  .stamp-box img{width:100%;height:100%;object-fit:contain;}
  .stamp-box.has-stamp{border-style:solid;cursor:default;}
  .stamp-remove{
    position:absolute;top:2px;left:2px;background:rgba(31,41,55,.65);color:#fff;
    border-radius:6px;width:18px;height:18px;font-size:11px;line-height:18px;padding:0;display:none;
  }
  .stamp-box.has-stamp .stamp-remove{display:block;}
  .stamp-label{font-size:12.5px;color:var(--muted);}
  .print-cta{display:flex;justify-content:center;align-items:center;gap:10px;margin-top:26px;flex-wrap:wrap;}
  .print-cta button{
    padding:13px 28px;border-radius:12px;font-size:15px;display:flex;align-items:center;gap:8px;
  }
  .print-cta .btn-primary:hover{background:var(--accent-dark);}
  .print-theme-select{
    border:1px solid var(--line);background:var(--panel);color:var(--ink);
    border-radius:10px;padding:11px 14px;font-family:inherit;font-size:13.5px;cursor:pointer;
  }
  .toast{
    position:fixed;bottom:24px;left:50%;transform:translateX(-50%);
    background:#1f2937;color:#fff;padding:10px 20px;border-radius:10px;
    font-size:13.5px;opacity:0;pointer-events:none;transition:.25s;z-index:50;
  }
  .toast.show{opacity:1;transform:translate(-50%,-4px);}
  .savedbar{
    display:none;background:var(--panel);border-radius:12px;box-shadow:var(--shadow);
    padding:14px 16px;margin-bottom:16px;font-size:13px;flex-direction:column;gap:8px;
  }
  .savedbar.show{display:flex;}
  .savedbar-title{color:var(--muted);font-size:12.5px;margin-bottom:2px;}
  .saved-search{
    border:1px solid var(--line);background:#f7fafe;border-radius:8px;
    padding:7px 10px;font-family:inherit;font-size:13px;margin-bottom:4px;
  }
  .saved-row{
    display:flex;align-items:center;justify-content:space-between;gap:10px;
    padding:9px 12px;background:#f7fafe;border-radius:9px;
  }
  body.dark .saved-row,body.dark .saved-search{background:#141b2a;}
  .saved-row .saved-info{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px;color:var(--ink);}
  .saved-row .saved-actions{display:flex;gap:6px;flex:0 0 auto;}
  /* -------- compact mode: used for real print AND for PDF export -------- */
  body.compact{background:#fff;color:#1f2937;}
  body.compact .card,body.compact .dates-row,body.compact .letterhead,body.compact .status-row,body.compact .customer-card{
    box-shadow:none;border:1px solid #e6ebf3;background:#fff;
  }
  body.compact .no-print{display:none !important;}
  body.compact .logo-box:not(.has-logo){display:none !important;}
  body.compact .stamp-box:not(.has-stamp){display:none !important;}
  body.compact .meta-field:has(input:placeholder-shown){display:none !important;}
  body.compact .company-name-input:placeholder-shown{display:none !important;}
  body.compact .customer-name-input:placeholder-shown{display:none !important;}
  body.compact .customer-field:has(input:placeholder-shown){display:none !important;}
  body.compact .payment-box-id:has(input:placeholder-shown){display:none !important;}
  body.compact .qr-row.collapsed{display:flex !important;}
  body.compact .qr-row:has(input:placeholder-shown){display:none !important;}
  body.compact .customer-card:has(.customer-name-input:placeholder-shown):not(:has(.customer-field:not(:has(input:placeholder-shown)))){display:none !important;}
  body.compact input{border:none !important;background:transparent !important;color:#1f2937 !important;}
  body.compact .status-select{display:none !important;}
  body.compact .wrap{max-width:100%;}
  /* -------- قالب مینیمال (ساده، سیاه‌وسفید) -------- */
  body.compact.theme-minimal *{box-shadow:none !important;}
  body.compact.theme-minimal .letterhead::before{display:none !important;}
  body.compact.theme-minimal .card,
  body.compact.theme-minimal .dates-row,
  body.compact.theme-minimal .letterhead,
  body.compact.theme-minimal .status-row,
  body.compact.theme-minimal .customer-card,
  body.compact.theme-minimal .payment-box,
  body.compact.theme-minimal .final-total-box{
    border:1px solid #ccc !important;border-radius:4px !important;background:#fff !important;
  }
  body.compact.theme-minimal .status-badge{
    background:none !important;border:1px solid #999 !important;color:#333 !important;
    border-radius:4px !important;padding:3px 10px !important;
  }
  body.compact.theme-minimal .final-total-label,
  body.compact.theme-minimal .final-total-value,
  body.compact.theme-minimal .company-name-input{color:#111 !important;}
  body.compact.theme-minimal .subtotal-marker{background:none !important;border-color:#ccc !important;}
  /* -------- قالب رسمی/اداری (کادردار، مستطیلی) -------- */
  body.compact.theme-formal *{box-shadow:none !important;}
  body.compact.theme-formal .wrap{border:2px solid #222;padding:14px;}
  body.compact.theme-formal .card,
  body.compact.theme-formal .dates-row,
  body.compact.theme-formal .letterhead,
  body.compact.theme-formal .status-row,
  body.compact.theme-formal .customer-card,
  body.compact.theme-formal .payment-box,
  body.compact.theme-formal .final-total-box{
    border:1.5px solid #222 !important;border-radius:0 !important;background:#fff !important;
  }
  body.compact.theme-formal .letterhead::before{display:none !important;}
  body.compact.theme-formal .status-badge{
    background:none !important;border:1.5px solid #222 !important;border-radius:0 !important;color:#111 !important;
  }
  body.compact.theme-formal .item-head{border-bottom:1.5px solid #222 !important;}
  body.compact.theme-formal .spec-row{border-bottom:1px dashed #999;}
  body.compact.theme-formal .company-name-input{text-align:center !important;width:100% !important;font-size:19px !important;}
  body.compact.theme-formal .final-total-label,
  body.compact.theme-formal .final-total-value{color:#111 !important;}
  body.compact.theme-formal .subtotal-marker{background:none !important;border-color:#222 !important;}
  /* -------- قالب مدرن بنفش-صورتی -------- */
  body.compact.theme-modern-purple{background:#faf7ff !important;}
  body.compact.theme-modern-purple .letterhead::before{background:linear-gradient(90deg,#7c3aed,#ec4899) !important;height:6px !important;}
  body.compact.theme-modern-purple .card,
  body.compact.theme-modern-purple .dates-row,
  body.compact.theme-modern-purple .letterhead,
  body.compact.theme-modern-purple .status-row,
  body.compact.theme-modern-purple .customer-card{border:1px solid #ecdffc !important;background:#fff !important;border-radius:16px !important;}
  body.compact.theme-modern-purple .final-total-box{background:#f5f0ff !important;}
  body.compact.theme-modern-purple .final-total-label,
  body.compact.theme-modern-purple .final-total-value,
  body.compact.theme-modern-purple .company-name-input{color:#6d28d9 !important;}
  body.compact.theme-modern-purple .payment-box{background:#faf5ff !important;}
  /* -------- قالب مدرن فیروزه‌ای -------- */
  body.compact.theme-modern-teal{background:#f5fffb !important;}
  body.compact.theme-modern-teal .letterhead::before{background:linear-gradient(90deg,#0ea5a5,#22c55e) !important;height:6px !important;}
  body.compact.theme-modern-teal .card,
  body.compact.theme-modern-teal .dates-row,
  body.compact.theme-modern-teal .letterhead,
  body.compact.theme-modern-teal .status-row,
  body.compact.theme-modern-teal .customer-card{border:1px solid #d7f5ee !important;background:#fff !important;border-radius:16px !important;}
  body.compact.theme-modern-teal .final-total-box{background:#e6faf5 !important;}
  body.compact.theme-modern-teal .final-total-label,
  body.compact.theme-modern-teal .final-total-value,
  body.compact.theme-modern-teal .company-name-input{color:#0f766e !important;}
  body.compact.theme-modern-teal .payment-box{background:#f0fdfa !important;}
  /* -------- قالب گرم و صمیمی (نارنجی) — مناسب فروشگاه/کسب‌وکار کوچک -------- */
  body.compact.theme-warm{background:#fffaf5 !important;}
  body.compact.theme-warm .letterhead::before{background:linear-gradient(90deg,#f97316,#f43f5e) !important;height:6px !important;}
  body.compact.theme-warm .card,
  body.compact.theme-warm .dates-row,
  body.compact.theme-warm .letterhead,
  body.compact.theme-warm .status-row,
  body.compact.theme-warm .customer-card{border:1px solid #ffe1c7 !important;background:#fff !important;border-radius:20px !important;}
  body.compact.theme-warm .final-total-box{background:#fff1e6 !important;}
  body.compact.theme-warm .final-total-label,
  body.compact.theme-warm .final-total-value,
  body.compact.theme-warm .company-name-input{color:#c2410c !important;}
  body.compact.theme-warm .payment-box{background:#fff7ed !important;}
  /* -------- قالب شرکتی سرمه‌ای -------- */
  body.compact.theme-navy{background:#fff !important;}
  body.compact.theme-navy .letterhead::before{background:#0b3d91 !important;height:6px !important;}
  body.compact.theme-navy .card,
  body.compact.theme-navy .dates-row,
  body.compact.theme-navy .letterhead,
  body.compact.theme-navy .status-row,
  body.compact.theme-navy .customer-card{border:1px solid #d7deef !important;background:#fff !important;border-radius:6px !important;}
  body.compact.theme-navy .final-total-box{background:#eef2fb !important;}
  body.compact.theme-navy .final-total-label,
  body.compact.theme-navy .final-total-value,
  body.compact.theme-navy .company-name-input{color:#0b3d91 !important;}
  body.compact.theme-navy .payment-box{background:#f5f7fc !important;}
  body.compact.theme-navy .item-head{border-bottom:1.5px solid #d7deef !important;}
  /* -------- قالب شیک تیره طلایی (مخصوص خروجی PDF/ایمیل) -------- */
  body.compact.theme-dark-lux{background:#10131a !important;color:#f1eadd !important;}
  body.compact.theme-dark-lux .letterhead::before{background:linear-gradient(90deg,#d4af37,#f4e2b8) !important;height:6px !important;}
  body.compact.theme-dark-lux .card,
  body.compact.theme-dark-lux .dates-row,
  body.compact.theme-dark-lux .letterhead,
  body.compact.theme-dark-lux .status-row,
  body.compact.theme-dark-lux .customer-card{border:1px solid #2a2f3d !important;background:#181c26 !important;border-radius:14px !important;}
  body.compact.theme-dark-lux .final-total-box{background:#20242e !important;border:1px solid #d4af37 !important;}
  body.compact.theme-dark-lux .final-total-label,
  body.compact.theme-dark-lux .final-total-value,
  body.compact.theme-dark-lux .company-name-input{color:#d4af37 !important;}
  body.compact.theme-dark-lux .payment-box{background:#1c202a !important;}
  body.compact.theme-dark-lux input{color:#f1eadd !important;}
  body.compact.theme-dark-lux .calc-label,
  body.compact.theme-dark-lux .item-body,
  body.compact.theme-dark-lux .title-input,
  body.compact.theme-dark-lux .subtitle-input,
  body.compact.theme-dark-lux .invoice-number,
  body.compact.theme-dark-lux .customer-title,
  body.compact.theme-dark-lux .pid-label,
  body.compact.theme-dark-lux .unit,
  body.compact.theme-dark-lux .unit-big{color:#cfd3dc !important;}
  body.compact.theme-dark-lux .item-head{border-bottom:1px solid #2a2f3d !important;}
  body.compact.theme-dark-lux .calc-row{border-bottom:1px solid #2a2f3d !important;}
  body.compact.theme-dark-lux .subtotal-marker{background:#1c202a !important;border-color:#2a2f3d !important;color:#cfd3dc !important;}
  body.compact.theme-dark-lux .final-total-words{color:#9a9fac !important;}
  /* -------- قالب بولد مشکی‌وسفید -------- */
  body.compact.theme-bold-mono{background:#fff !important;}
  body.compact.theme-bold-mono *{box-shadow:none !important;}
  body.compact.theme-bold-mono .letterhead::before{background:#000 !important;height:7px !important;}
  body.compact.theme-bold-mono .card,
  body.compact.theme-bold-mono .dates-row,
  body.compact.theme-bold-mono .letterhead,
  body.compact.theme-bold-mono .status-row,
  body.compact.theme-bold-mono .customer-card,
  body.compact.theme-bold-mono .payment-box,
  body.compact.theme-bold-mono .final-total-box{border:2px solid #000 !important;background:#fff !important;border-radius:0 !important;}
  body.compact.theme-bold-mono .status-badge{background:#000 !important;color:#fff !important;border-radius:0 !important;font-weight:800 !important;}
  body.compact.theme-bold-mono .final-total-label,
  body.compact.theme-bold-mono .final-total-value,
  body.compact.theme-bold-mono .company-name-input{color:#000 !important;font-weight:800 !important;}
  body.compact.theme-bold-mono .item-head{border-bottom:2px solid #000 !important;}
  body.compact.theme-bold-mono .title-input{font-weight:800 !important;}
  @media print{
    body{padding:0;}
  }
</style>
</head>
<body>
<div class="draft-watermark no-print-exempt" id="draftWatermark">پیش‌نویس</div>
<div class="wrap">
  <div class="toolbar">
    <div class="toolbar-left">
      <h1>فاکتورساز</h1>
      <button class="icon-btn no-print" id="btnDark" title="حالت تاریک/روشن">🌙</button>
      <select class="currency-select no-print" id="currencySelect" title="واحد پول">
        <option value="toman">تومان</option>
        <option value="rial">ریال</option>
        <option value="dollar">دلار</option>
      </select>
    </div>
    <div class="btns no-print">
      <button class="btn-ghost" id="btnLoadList">فاکتورهای ذخیره‌شده</button>
      <button class="btn-ghost" id="btnNew">فاکتور جدید</button>
      <button class="btn-primary" id="btnSave">ذخیره</button>
    </div>
  </div>
  <div class="letterhead">
    <div class="logo-box" id="logoBox">
      <span id="logoPlaceholder">افزودن<br>لوگو</span>
      <img id="logoImg" style="display:none">
      <button class="logo-remove no-print" id="logoRemove" title="حذف لوگو">✕</button>
      <input type="file" id="logoFile" accept="image/*" style="display:none">
    </div>
    <div class="letterhead-fields">
      <input type="text" class="company-name-input" id="companyName" value="" placeholder="نام شرکت">
      <div class="letterhead-meta">
        <div class="meta-field"><span class="meta-icon">🌐</span><input type="text" id="companySite" value="" placeholder="آدرس سایت"></div>
        <div class="meta-field"><span class="meta-icon">📞</span><input type="text" id="companyPhone" value="" placeholder="تلفن تماس"></div>
        <div class="meta-field"><span class="meta-icon">✉️</span><input type="text" id="companyEmail" value="" placeholder="ایمیل"></div>
        <div class="meta-field"><span class="meta-icon">📍</span><input type="text" id="companyAddress" value="" placeholder="آدرس / کد اقتصادی"></div>
      </div>
    </div>
  </div>
  <div class="status-row">
    <div class="invoice-number">شماره فاکتور: <b id="invoiceNumberDisplay">پیش‌نویس</b></div>
    <div class="status-wrap">
      <span class="status-badge" id="statusBadge">درانتظار پرداخت</span>
      <select class="status-select no-print" id="statusOverride" title="وضعیت فاکتور">
        <option value="auto">خودکار</option>
        <option value="paid">پرداخت‌شده</option>
        <option value="partial">پرداخت جزئی</option>
        <option value="pending">درانتظار پرداخت</option>
        <option value="overdue">معوق</option>
        <option value="canceled">لغو شده</option>
      </select>
    </div>
  </div>
  <div class="lock-banner no-print" id="lockBanner" style="display:none;">
    <span>🔒 این فاکتور «پرداخت‌شده» علامت خورده و برای جلوگیری از تغییر اشتباهی قفل شده است.</span>
    <button class="btn-ghost btn-sm" id="unlockEditBtn">باز کردن قفل ویرایش</button>
  </div>
  <div class="customer-card">
    <div class="customer-title">اطلاعات مشتری</div>
    <input type="text" class="customer-name-input" id="customerName" placeholder="نام مشتری / شرکت خریدار">
    <div class="letterhead-meta">
      <div class="meta-field customer-field"><span class="meta-icon">📞</span><input type="text" id="customerPhone" placeholder="تلفن مشتری"></div>
      <div class="meta-field customer-field"><span class="meta-icon">📍</span><input type="text" id="customerAddress" placeholder="آدرس مشتری"></div>
      <div class="meta-field customer-field"><span class="meta-icon">🪪</span><input type="text" id="customerCode" placeholder="کد ملی / اقتصادی"></div>
    </div>
  </div>
  <div class="savedbar" id="savedBar">
    <div class="savedbar-title">فاکتورهای ذخیره‌شده:</div>
    <input type="text" class="saved-search" id="savedSearch" placeholder="جستجو بر اساس نام شرکت، تاریخ یا شناسه پرداخت...">
    <div id="savedList"></div>
  </div>
  <div class="dates-row">
    <div class="date-box">
      <label>تاریخ صورت‌حساب</label>
      <input type="text" id="dateIssue" value="">
    </div>
    <div class="date-box">
      <label>تاریخ سررسید</label>
      <input type="text" id="dateDue" value="">
    </div>
    <div class="date-box">
      <label>تاریخ پرداخت</label>
      <input type="text" id="datePaid" value="">
    </div>
  </div>
  <div id="itemsContainer"></div>
  <div class="template-row no-print">
    <select class="template-select" id="templateSelect">
      <option value="" selected disabled>+ افزودن ردیف از قالب آماده...</option>
      <option value="hosting">قالب هاستینگ</option>
      <option value="design">قالب طراحی وب‌سایت</option>
      <option value="domain">قالب ثبت دامنه</option>
      <option value="ssl">قالب گواهی SSL</option>
      <option value="support">قالب پشتیبانی ماهانه</option>
    </select>
  </div>
  <button class="add-item-btn no-print" id="btnAddItem">+ افزودن ردیف فاکتور</button>
  <div class="card summary-card">
    <div class="divider-label">صورت‌حساب</div>
    <div class="calc-table">
      <div class="calc-row">
        <span class="calc-label">خالص فاکتور</span>
        <span class="calc-val" id="netTotalDisplay">۰ تومان</span>
      </div>
      <div class="calc-row">
        <span class="calc-label">تخفیف <input type="text" class="tax-pct" id="discountPct" value="0">٪</span>
        <span class="calc-val muted" id="discountAmountDisplay">۰ تومان</span>
      </div>
      <div class="calc-row">
        <span class="calc-label">مالیات بر ارزش افزوده <input type="text" class="tax-pct" id="taxPct" value="10">٪</span>
        <span class="calc-val muted" id="taxAmountDisplay">۰ تومان</span>
      </div>
    </div>
    <div class="final-total-box">
      <div class="final-total-top">
        <span class="final-total-label">مبلغ نهایی قابل پرداخت</span>
        <span class="final-total-value" id="finalTotalDisplay">۰ تومان</span>
      </div>
      <div class="final-total-words" id="finalTotalWords"></div>
    </div>
    <div class="payment-box">
      <div class="payment-box-main">
        <span class="paid-label-big">مجموع پرداخت‌شده</span>
        <input type="text" id="paidTotalInput" value="0" class="paid-input-big">
        <span class="unit-big">تومان</span>
      </div>
      <div class="payment-box-id">
        <span class="pid-label">شناسه پرداخت:</span>
        <input type="text" id="paymentIdInput" placeholder="شماره پیگیری" class="pid-input">
      </div>
    </div>
    <button type="button" class="qr-toggle-btn no-print" id="toggleQrBtn">+ افزودن لینک/QR پرداخت آنلاین (اختیاری)</button>
    <div class="qr-row collapsed" id="qrRowWrap">
      <div class="label">لینک/کد پرداخت آنلاین<br><input type="text" id="paymentLinkInput" placeholder="مثلاً https://pay.example.com/..."></div>
      <div class="qr-box" id="qrCodeBox"></div>
    </div>
  </div>
  <div class="card stamp-card">
    <div class="stamp-box" id="stampBox">
      <span id="stampPlaceholder">افزودن<br>مهر/امضا</span>
      <img id="stampImg" style="display:none">
      <button class="stamp-remove no-print" id="stampRemove" title="حذف مهر/امضا">✕</button>
      <input type="file" id="stampFile" accept="image/*" style="display:none">
    </div>
    <div class="stamp-label">مهر و امضای صادرکننده</div>
  </div>
  <div class="print-cta no-print">
    <select class="print-theme-select" id="printTheme" title="قالب چاپ/PDF">
      <optgroup label="رسمی">
        <option value="classic">کلاسیک (رنگی)</option>
        <option value="formal">رسمی/اداری (کادردار)</option>
        <option value="navy">شرکتی سرمه‌ای</option>
        <option value="minimal">مینیمال (ساده)</option>
      </optgroup>
      <optgroup label="مدرن">
        <option value="modern-purple">مدرن بنفش-صورتی</option>
        <option value="modern-teal">مدرن فیروزه‌ای</option>
        <option value="bold-mono">بولد مشکی‌وسفید</option>
      </optgroup>
      <optgroup label="غیررسمی">
        <option value="warm">گرم و صمیمی (نارنجی)</option>
        <option value="dark-lux">شیک تیره طلایی (مخصوص PDF)</option>
      </optgroup>
    </select>
    <button class="btn-primary" id="btnPrint">🖨 چاپ صورت‌حساب</button>
    <button class="btn-ghost" id="btnPdf">📄 دانلود PDF</button>
  </div>
</div>
<div class="toast" id="toast"></div>
<template id="itemTemplate">
  <div class="card item-card" draggable="true">
    <div class="item-head">
      <div class="item-head-left">
        <span class="drag-handle no-print" title="جابه‌جایی">☰</span>
        <input type="text" class="title-input" placeholder="عنوان سرویس / محصول" value="">
      </div>
      <div class="amount-input-wrap">
        <input type="text" class="amount-input item-amount" value="0">
        <span class="unit">تومان</span>
        <button class="drag-remove item-remove no-print" title="حذف ردیف">✕</button>
      </div>
    </div>
    <div class="item-body">
      <input type="text" class="subtitle-input" placeholder="توضیح یا بازه زمانی (مثلاً 2025-04-17 تا 2026-04-16)" value="">
      <div class="specs"></div>
      <button class="add-spec-btn no-print">+ افزودن مشخصه</button>
    </div>
  </div>
</template>
<template id="specTemplate">
  <div class="spec-row">
    <span class="spec-icon">▫️</span>
    <input type="text" class="spec-key" placeholder="عنوان مشخصه" value="">
    <input type="text" class="spec-val" placeholder="مقدار" value="">
    <button class="spec-remove no-print">✕</button>
  </div>
</template>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
const itemsContainer = document.getElementById('itemsContainer');
const itemTemplate = document.getElementById('itemTemplate');
const specTemplate = document.getElementById('specTemplate');
const faDigits = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
function toFaDigits(str){
  return String(str).replace(/[0-9]/g, d => faDigits[d]);
}
function toEnDigits(str){
  return String(str).replace(/[۰-۹]/g, d => faDigits.indexOf(d));
}
function parseNum(str){
  const n = parseFloat(toEnDigits(str).replace(/[^0-9.-]/g,''));
  return isNaN(n) ? 0 : n;
}
const currencyLabels = {toman:'تومان', rial:'ریال', dollar:'دلار'};
function currencyLabel(){
  const sel = document.getElementById('currencySelect');
  return currencyLabels[sel ? sel.value : 'toman'] || 'تومان';
}
function formatMoney(n){
  n = Math.round(n);
  return toFaDigits(n.toLocaleString('en-US')) + ' ' + currencyLabel();
}
function updateCurrencyLabels(){
  const label = currencyLabel();
  document.querySelectorAll('.unit, .unit-big').forEach(el => { el.textContent = label; });
}
// -------- تبدیل عدد به حروف فارسی --------
const p2wOnes = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
const p2wTeens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
const p2wTens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
const p2wHundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
const p2wScales = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];
function threeDigitToPersianWords(n){
  const h = Math.floor(n / 100), rem = n % 100, t = Math.floor(rem / 10), o = rem % 10;
  const parts = [];
  if(h > 0) parts.push(p2wHundreds[h]);
  if(t === 1){ parts.push(p2wTeens[o]); }
  else {
    if(t > 1) parts.push(p2wTens[t]);
    if(o > 0) parts.push(p2wOnes[o]);
  }
  return parts.join(' و ');
}
function numberToPersianWords(num){
  num = Math.round(Math.abs(num));
  if(num === 0) return 'صفر';
  const groups = [];
  let n = num;
  while(n > 0){ groups.push(n % 1000); n = Math.floor(n / 1000); }
  const parts = [];
  for(let i = groups.length - 1; i >= 0; i--){
    if(groups[i] === 0) continue;
    let word = threeDigitToPersianWords(groups[i]);
    if(p2wScales[i]) word += ' ' + p2wScales[i];
    parts.push(word);
  }
  return parts.join(' و ');
}
function debounce(fn, ms){
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}
// -------- آیکون مشخصات فنی --------
const specIconRules = [
  [/ram/i,'🧠'], [/cpu/i,'⚙️'], [/bandwidth|پهنای/i,'📶'], [/disk|دیسک/i,'💾'],
  [/ip/i,'🌐'], [/control ?panel|کنترل/i,'🎛️'], [/mysql|دیتابیس/i,'🗄️'],
  [/^ep$/i,'⚡'], [/دامنه|domain/i,'🔗'], [/ssl|امنیت/i,'🔒']
];
function iconForKey(key){
  for(const [re,icon] of specIconRules){ if(re.test(key)) return icon; }
  return '▫️';
}
function addSpec(specsEl, key='', val=''){
  const node = specTemplate.content.cloneNode(true);
  const row = node.querySelector('.spec-row');
  const keyInput = row.querySelector('.spec-key');
  const iconEl = row.querySelector('.spec-icon');
  keyInput.value = toFaDigits(key);
  row.querySelector('.spec-val').value = toFaDigits(val);
  iconEl.textContent = iconForKey(key);
  keyInput.addEventListener('input', () => { iconEl.textContent = iconForKey(keyInput.value); });
  row.querySelector('.spec-remove').addEventListener('click', () => row.remove());
  specsEl.appendChild(row);
}
function addItem(data={}){
  const node = itemTemplate.content.cloneNode(true);
  const card = node.querySelector('.item-card');
  card.querySelector('.title-input').value = toFaDigits(data.title ?? 'سرویس جدید');
  card.querySelector('.item-amount').value = toFaDigits(data.amount ?? 0);
  card.querySelector('.subtitle-input').value = toFaDigits(data.subtitle ?? '');
  const specsEl = card.querySelector('.specs');
  const specs = data.specs && data.specs.length ? data.specs : [
    {k:'مشخصه', v:'مقدار'}
  ];
  specs.forEach(s => addSpec(specsEl, s.k, s.v));
  card.querySelector('.item-remove').addEventListener('click', () => {
    card.remove();
    recalc();
  });
  card.querySelector('.add-spec-btn').addEventListener('click', () => addSpec(specsEl));
  card.querySelector('.item-amount').addEventListener('input', recalc);
  card.querySelector('.unit').textContent = currencyLabel();
  itemsContainer.appendChild(card);
}
// -------- قالب‌های آماده --------
const invoiceTemplates = {
  hosting: {
    title: 'سرویس هاستینگ', subtitle: '',
    specs: [
      {k:'Bandwidth(GB)', v:'200'}, {k:'DiskUsage(MB)', v:'10000'},
      {k:'ControlPanel', v:'directadmin'}, {k:'Ram(MB)', v:'2000'}, {k:'CPU-Core', v:'2'}
    ]
  },
  design: {
    title: 'طراحی وب‌سایت', subtitle: '',
    specs: [
      {k:'تعداد صفحات', v:'5'}, {k:'نوع طراحی', v:'ریسپانسیو'},
      {k:'پنل مدیریت', v:'دارد'}, {k:'پشتیبانی', v:'۱ ماه رایگان'}
    ]
  },
  domain: {
    title: 'ثبت دامنه', subtitle: '',
    specs: [
      {k:'نوع دامنه', v:'.ir'}, {k:'مدت اعتبار', v:'۱ ساله'}, {k:'DNS', v:'اختصاصی'}
    ]
  },
  ssl: {
    title: 'گواهی SSL', subtitle: '',
    specs: [
      {k:'نوع گواهی', v:'DV'}, {k:'مدت اعتبار', v:'۱ ساله'}
    ]
  },
  support: {
    title: 'قرارداد پشتیبانی ماهانه', subtitle: '',
    specs: [
      {k:'مدت قرارداد', v:'۱ ماهه'}, {k:'تعداد درخواست', v:'نامحدود'}, {k:'زمان پاسخ‌گویی', v:'حداکثر ۲۴ ساعت'}
    ]
  }
};
function addTemplateItem(key){
  const t = invoiceTemplates[key];
  if(!t) return;
  addItem(JSON.parse(JSON.stringify(t)));
  recalc();
}
// -------- جابه‌جایی ردیف‌ها با درگ‌اند‌دراپ --------
let draggedCard = null;
itemsContainer.addEventListener('dragstart', (e) => {
  if(!e.target.classList.contains('drag-handle')) return;
  draggedCard = e.target.closest('.item-card');
  e.dataTransfer.effectAllowed = 'move';
  setTimeout(() => draggedCard && draggedCard.classList.add('dragging'), 0);
});
itemsContainer.addEventListener('dragend', () => {
  if(draggedCard) draggedCard.classList.remove('dragging');
  draggedCard = null;
  recalc();
});
itemsContainer.addEventListener('dragover', (e) => {
  if(!draggedCard) return;
  e.preventDefault();
  const afterElement = getDragAfterElement(itemsContainer, e.clientY);
  if(afterElement == null){ itemsContainer.appendChild(draggedCard); }
  else { itemsContainer.insertBefore(draggedCard, afterElement); }
});
function getDragAfterElement(container, y){
  const cards = [...container.querySelectorAll('.item-card:not(.dragging)')];
  return cards.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height/2;
    if(offset < 0 && offset > closest.offset){ return {offset, element: child}; }
    return closest;
  }, {offset: -Infinity}).element;
}
// -------- جمع جزئی هر ۳ ردیف --------
function rebuildSubtotals(){
  itemsContainer.querySelectorAll('.subtotal-marker').forEach(m => m.remove());
  const cards = [...itemsContainer.querySelectorAll('.item-card')];
  if(cards.length <= 3) return;
  let running = 0;
  cards.forEach((card, idx) => {
    running += parseNum(card.querySelector('.item-amount').value);
    const isLast = idx === cards.length - 1;
    if((idx + 1) % 3 === 0 && !isLast){
      const marker = document.createElement('div');
      marker.className = 'subtotal-marker no-print';
      marker.innerHTML = `<span>جمع جزئی تا ردیف ${toFaDigits(idx + 1)}</span><span>${formatMoney(running)}</span>`;
      card.after(marker);
    }
  });
}
// -------- محاسبات کلی --------
function recalc(){
  let net = 0;
  document.querySelectorAll('.item-amount').forEach(inp => { net += parseNum(inp.value); });
  document.getElementById('netTotalDisplay').textContent = formatMoney(net);
  const discountPct = parseNum(document.getElementById('discountPct').value);
  const discountAmount = net * discountPct / 100;
  document.getElementById('discountAmountDisplay').textContent = formatMoney(discountAmount);
  const taxable = net - discountAmount;
  const taxPct = parseNum(document.getElementById('taxPct').value);
  const taxAmount = taxable * taxPct / 100;
  document.getElementById('taxAmountDisplay').textContent = formatMoney(taxAmount);
  const finalTotal = taxable + taxAmount;
  document.getElementById('finalTotalDisplay').textContent = formatMoney(finalTotal);
  document.getElementById('finalTotalWords').textContent =
    finalTotal > 0 ? (numberToPersianWords(finalTotal) + ' ' + currencyLabel()) : '';
  rebuildSubtotals();
  updateStatusBadge(finalTotal);
  applyLockState();
}
// -------- وضعیت فاکتور --------
function updateStatusBadge(finalTotal){
  const badge = document.getElementById('statusBadge');
  const override = document.getElementById('statusOverride').value;
  const paid = parseNum(document.getElementById('paidTotalInput').value);
  let key, text;
  if(override !== 'auto'){
    key = override;
  } else if(finalTotal <= 0){
    key = 'pending';
  } else if(paid >= finalTotal - 1){
    key = 'paid';
  } else if(paid > 0){
    key = 'partial';
  } else {
    key = 'pending';
  }
  const labels = {paid:'پرداخت‌شده',partial:'پرداخت جزئی',pending:'درانتظار پرداخت',overdue:'معوق',canceled:'لغو شده'};
  text = labels[key] || 'درانتظار پرداخت';
  badge.className = 'status-badge ' + key;
  badge.textContent = text;
}
// -------- قفل ویرایش پس از پرداخت‌شده --------
let editUnlocked = false;
function setFieldsDisabled(disabled){
  const exemptIds = ['statusOverride','btnPrint','btnPdf','btnSave','btnNew','btnLoadList','btnDark','unlockEditBtn','savedSearch','printTheme','currencySelect'];
  document.querySelectorAll('.wrap input, .wrap select').forEach(el => {
    if(exemptIds.includes(el.id)) return;
    if(el.closest('#savedBar')) return;
    el.disabled = disabled;
  });
  document.querySelectorAll('.wrap button').forEach(el => {
    if(exemptIds.includes(el.id)) return;
    if(el.closest('#savedBar')) return;
    el.disabled = disabled;
  });
  document.querySelectorAll('.item-card').forEach(card => { card.draggable = !disabled; });
}
function applyLockState(){
  const badge = document.getElementById('statusBadge');
  const banner = document.getElementById('lockBanner');
  const isPaid = badge.classList.contains('paid');
  if(isPaid && !editUnlocked){
    banner.style.display = 'flex';
    setFieldsDisabled(true);
  } else {
    banner.style.display = 'none';
    setFieldsDisabled(false);
  }
}
document.getElementById('unlockEditBtn').addEventListener('click', () => {
  if(confirm('این فاکتور «پرداخت‌شده» علامت خورده. آیا مطمئنید می‌خواهید آن را ویرایش کنید؟')){
    editUnlocked = true;
    applyLockState();
  }
});
document.getElementById('statusOverride').addEventListener('change', () => recalc());
document.getElementById('paidTotalInput').addEventListener('input', () => recalc());
document.getElementById('discountPct').addEventListener('input', recalc);
document.getElementById('taxPct').addEventListener('input', recalc);
// -------- لوگو --------
const logoBox = document.getElementById('logoBox');
const logoImg = document.getElementById('logoImg');
const logoPlaceholder = document.getElementById('logoPlaceholder');
const logoFile = document.getElementById('logoFile');
const logoRemove = document.getElementById('logoRemove');
let logoDataUrl = '';
logoBox.addEventListener('click', (e) => {
  if(e.target === logoRemove) return;
  if(!logoDataUrl) logoFile.click();
});
logoFile.addEventListener('change', () => {
  const file = logoFile.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = () => {
    logoDataUrl = reader.result;
    logoImg.src = logoDataUrl;
    logoImg.style.display = 'block';
    logoPlaceholder.style.display = 'none';
    logoBox.classList.add('has-logo');
  };
  reader.readAsDataURL(file);
});
logoRemove.addEventListener('click', (e) => {
  e.stopPropagation();
  logoDataUrl = '';
  logoImg.src = '';
  logoImg.style.display = 'none';
  logoPlaceholder.style.display = 'block';
  logoBox.classList.remove('has-logo');
  logoFile.value = '';
});
// -------- مهر و امضا --------
const stampBox = document.getElementById('stampBox');
const stampImg = document.getElementById('stampImg');
const stampPlaceholder = document.getElementById('stampPlaceholder');
const stampFile = document.getElementById('stampFile');
const stampRemove = document.getElementById('stampRemove');
let stampDataUrl = '';
stampBox.addEventListener('click', (e) => {
  if(e.target === stampRemove) return;
  if(!stampDataUrl) stampFile.click();
});
stampFile.addEventListener('change', () => {
  const file = stampFile.files[0];
  if(!file) return;
  const reader = new FileReader();
  reader.onload = () => {
    stampDataUrl = reader.result;
    stampImg.src = stampDataUrl;
    stampImg.style.display = 'block';
    stampPlaceholder.style.display = 'none';
    stampBox.classList.add('has-stamp');
  };
  reader.readAsDataURL(file);
});
stampRemove.addEventListener('click', (e) => {
  e.stopPropagation();
  stampDataUrl = '';
  stampImg.src = '';
  stampImg.style.display = 'none';
  stampPlaceholder.style.display = 'block';
  stampBox.classList.remove('has-stamp');
  stampFile.value = '';
});
// -------- کد QR --------
let qrInstance = null;
function regenerateQR(){
  const val = document.getElementById('paymentLinkInput').value.trim();
  const box = document.getElementById('qrCodeBox');
  box.innerHTML = '';
  qrInstance = null;
  if(!val) return;
  try{
    qrInstance = new QRCode(box, { text: val, width: 64, height: 64, correctLevel: QRCode.CorrectLevel.M });
  }catch(e){ /* کتابخانه بارگذاری نشده یا خطا؛ بی‌صدا رد می‌شویم */ }
}
document.getElementById('paymentLinkInput').addEventListener('input', debounce(regenerateQR, 350));
document.getElementById('toggleQrBtn').addEventListener('click', () => {
  const wrap = document.getElementById('qrRowWrap');
  const btn = document.getElementById('toggleQrBtn');
  const nowCollapsed = wrap.classList.toggle('collapsed');
  btn.textContent = nowCollapsed
    ? '+ افزودن لینک/QR پرداخت آنلاین (اختیاری)'
    : '− پنهان کردن لینک/QR پرداخت آنلاین';
});
// -------- حالت تاریک --------
const btnDark = document.getElementById('btnDark');
btnDark.addEventListener('click', () => {
  document.body.classList.toggle('dark');
  btnDark.textContent = document.body.classList.contains('dark') ? '☀️' : '🌙';
});
// -------- تبدیل زنده‌ی همه‌ی اعداد به رقم فارسی --------
document.addEventListener('input', (e) => {
  const el = e.target;
  if(el.tagName !== 'INPUT' || el.type !== 'text') return;
  const pos = el.selectionStart;
  const converted = toFaDigits(el.value);
  if(converted !== el.value){
    el.value = converted;
    if(pos !== null) el.setSelectionRange(pos, pos);
  }
});
document.getElementById('btnAddItem').addEventListener('click', () => { addItem(); recalc(); });
document.getElementById('currencySelect').addEventListener('change', () => {
  updateCurrencyLabels();
  recalc();
});
document.getElementById('templateSelect').addEventListener('change', (e) => {
  const val = e.target.value;
  if(val) addTemplateItem(val);
  e.target.value = '';
});
// -------- واترمارک پیش‌نویس --------
function updateDraftWatermark(){
  const wm = document.getElementById('draftWatermark');
  if(currentInvoiceId){ wm.classList.remove('show'); }
  else { wm.classList.add('show'); }
}
// -------- چاپ --------
const ALL_PRINT_THEME_CLASSES = [
  'theme-minimal','theme-formal','theme-modern-purple','theme-modern-teal',
  'theme-warm','theme-navy','theme-dark-lux','theme-bold-mono'
];
function applyPrintTheme(){
  document.body.classList.remove(...ALL_PRINT_THEME_CLASSES);
  const theme = document.getElementById('printTheme').value;
  if(theme !== 'classic') document.body.classList.add('theme-' + theme);
}
function clearPrintTheme(){
  document.body.classList.remove(...ALL_PRINT_THEME_CLASSES);
}
let wasDark = false;
window.addEventListener('beforeprint', () => {
  wasDark = document.body.classList.contains('dark');
  document.body.classList.remove('dark');
  document.body.classList.add('compact');
  applyPrintTheme();
});
window.addEventListener('afterprint', () => {
  document.body.classList.remove('compact');
  clearPrintTheme();
  if(wasDark) document.body.classList.add('dark');
});
document.getElementById('btnPrint').addEventListener('click', () => window.print());
// -------- خروجی PDF --------
document.getElementById('btnPdf').addEventListener('click', () => {
  const wasDarkPdf = document.body.classList.contains('dark');
  document.body.classList.remove('dark');
  document.body.classList.add('compact');
  applyPrintTheme();
  const el = document.querySelector('.wrap');
  const opt = {
    margin: 8,
    filename: 'invoice.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };
  html2pdf().set(opt).from(el).save().then(() => {
    document.body.classList.remove('compact');
    clearPrintTheme();
    if(wasDarkPdf) document.body.classList.add('dark');
  }).catch(() => {
    document.body.classList.remove('compact');
    clearPrintTheme();
    if(wasDarkPdf) document.body.classList.add('dark');
    showToast('خروجی PDF ناموفق بود؛ از دکمه چاپ استفاده کنید');
  });
});
function showToast(msg){
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2200);
}
let currentInvoiceId = null;
let currentInvoiceNumber = null;
function collectPayload(){
  const items = [...document.querySelectorAll('.item-card')].map(card => ({
    title: card.querySelector('.title-input').value,
    subtitle: card.querySelector('.subtitle-input').value,
    amount: parseNum(card.querySelector('.item-amount').value),
    specs: [...card.querySelectorAll('.spec-row')].map(r => ({
      k: r.querySelector('.spec-key').value,
      v: r.querySelector('.spec-val').value
    }))
  }));
  return {
    id: currentInvoiceId,
    invoiceNumber: currentInvoiceNumber,
    companyName: document.getElementById('companyName').value,
    companySite: document.getElementById('companySite').value,
    companyPhone: document.getElementById('companyPhone').value,
    companyEmail: document.getElementById('companyEmail').value,
    companyAddress: document.getElementById('companyAddress').value,
    logo: logoDataUrl,
    stamp: stampDataUrl,
    customerName: document.getElementById('customerName').value,
    customerPhone: document.getElementById('customerPhone').value,
    customerAddress: document.getElementById('customerAddress').value,
    customerCode: document.getElementById('customerCode').value,
    dateIssue: document.getElementById('dateIssue').value,
    dateDue: document.getElementById('dateDue').value,
    datePaid: document.getElementById('datePaid').value,
    items,
    discountPct: document.getElementById('discountPct').value,
    taxPct: document.getElementById('taxPct').value,
    paidTotal: document.getElementById('paidTotalInput').value,
    paymentId: document.getElementById('paymentIdInput').value,
    paymentLink: document.getElementById('paymentLinkInput').value,
    statusOverride: document.getElementById('statusOverride').value,
    currency: document.getElementById('currencySelect').value
  };
}
function loadPayload(p){
  editUnlocked = false;
  currentInvoiceId = p.id ?? null;
  currentInvoiceNumber = p.invoiceNumber ?? null;
  document.getElementById('invoiceNumberDisplay').textContent = currentInvoiceNumber ? toFaDigits(currentInvoiceNumber) : 'پیش‌نویس';
  document.getElementById('currencySelect').value = p.currency ?? 'toman';
  updateDraftWatermark();
  document.getElementById('companyName').value = toFaDigits(p.companyName ?? '');
  document.getElementById('companySite').value = toFaDigits(p.companySite ?? '');
  document.getElementById('companyPhone').value = toFaDigits(p.companyPhone ?? '');
  document.getElementById('companyEmail').value = toFaDigits(p.companyEmail ?? '');
  document.getElementById('companyAddress').value = toFaDigits(p.companyAddress ?? '');
  document.getElementById('customerName').value = toFaDigits(p.customerName ?? '');
  document.getElementById('customerPhone').value = toFaDigits(p.customerPhone ?? '');
  document.getElementById('customerAddress').value = toFaDigits(p.customerAddress ?? '');
  document.getElementById('customerCode').value = toFaDigits(p.customerCode ?? '');
  if(p.logo){
    logoDataUrl = p.logo;
    logoImg.src = logoDataUrl; logoImg.style.display = 'block';
    logoPlaceholder.style.display = 'none'; logoBox.classList.add('has-logo');
  } else {
    logoDataUrl = ''; logoImg.src = ''; logoImg.style.display = 'none';
    logoPlaceholder.style.display = 'block'; logoBox.classList.remove('has-logo');
  }
  if(p.stamp){
    stampDataUrl = p.stamp;
    stampImg.src = stampDataUrl; stampImg.style.display = 'block';
    stampPlaceholder.style.display = 'none'; stampBox.classList.add('has-stamp');
  } else {
    stampDataUrl = ''; stampImg.src = ''; stampImg.style.display = 'none';
    stampPlaceholder.style.display = 'block'; stampBox.classList.remove('has-stamp');
  }
  document.getElementById('dateIssue').value = toFaDigits(p.dateIssue ?? '');
  document.getElementById('dateDue').value = toFaDigits(p.dateDue ?? '');
  document.getElementById('datePaid').value = toFaDigits(p.datePaid ?? '');
  document.getElementById('discountPct').value = toFaDigits(p.discountPct ?? 0);
  document.getElementById('taxPct').value = toFaDigits(p.taxPct ?? 10);
  document.getElementById('paidTotalInput').value = toFaDigits(p.paidTotal ?? 0);
  document.getElementById('paymentIdInput').value = toFaDigits(p.paymentId ?? '');
  document.getElementById('paymentLinkInput').value = p.paymentLink ?? '';
  const qrWrap = document.getElementById('qrRowWrap');
  const qrBtn = document.getElementById('toggleQrBtn');
  if((p.paymentLink ?? '').trim()){
    qrWrap.classList.remove('collapsed');
    qrBtn.textContent = '− پنهان کردن لینک/QR پرداخت آنلاین';
  } else {
    qrWrap.classList.add('collapsed');
    qrBtn.textContent = '+ افزودن لینک/QR پرداخت آنلاین (اختیاری)';
  }
  document.getElementById('statusOverride').value = p.statusOverride ?? 'auto';
  itemsContainer.innerHTML = '';
  (p.items || []).forEach(it => addItem(it));
  updateCurrencyLabels();
  regenerateQR();
  recalc();
}
// -------- اعتبارسنجی قبل از ذخیره --------
function parseDateParts(str){
  const clean = toEnDigits(String(str || '')).trim();
  const parts = clean.split(/[\/\-]/).map(x => parseInt(x, 10));
  if(parts.length < 3 || parts.some(isNaN)) return null;
  return parts;
}
function compareDateParts(a, b){
  for(let i = 0; i < 3; i++){
    if(a[i] !== b[i]) return a[i] < b[i] ? -1 : 1;
  }
  return 0;
}
document.getElementById('btnSave').addEventListener('click', async () => {
  const issueParts = parseDateParts(document.getElementById('dateIssue').value);
  const dueParts = parseDateParts(document.getElementById('dateDue').value);
  if(issueParts && dueParts && compareDateParts(dueParts, issueParts) < 0){
    if(!confirm('تاریخ سررسید زودتر از تاریخ صورت‌حساب است. آیا مطمئنید می‌خواهید ذخیره کنید؟')) return;
  }
  if(document.querySelectorAll('.item-card').length === 0){
    if(!confirm('هیچ ردیفی به فاکتور اضافه نشده است. آیا مطمئنید می‌خواهید ذخیره کنید؟')) return;
  }
  const payload = collectPayload();
  try{
    const res = await fetch('?', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: 'action=save&payload=' + encodeURIComponent(JSON.stringify(payload))
    });
    const data = await res.json();
    if(data.ok){
      currentInvoiceId = data.id;
      currentInvoiceNumber = data.invoiceNumber;
      document.getElementById('invoiceNumberDisplay').textContent = toFaDigits(currentInvoiceNumber);
      updateDraftWatermark();
      showToast('فاکتور ذخیره شد');
    } else { showToast('ذخیره ناموفق بود'); }
  }catch(e){ showToast('خطا در ذخیره‌سازی'); }
});
document.getElementById('btnNew').addEventListener('click', () => {
  if(confirm('یک فاکتور خالی جدید ساخته شود؟')){
    loadPayload({});
    addItem();
    recalc();
  }
});
async function refreshSavedList(){
  try{
    const res = await fetch('?action=list');
    const list = await res.json();
    window._loadedInvoices = list;
    renderSavedList(list);
  }catch(e){ showToast('خطا در بارگذاری لیست'); }
}
function renderSavedList(list){
  const listEl = document.getElementById('savedList');
  listEl.innerHTML = '';
  if(!list.length){
    listEl.innerHTML = '<div style="color:var(--muted);padding:6px 2px;">فاکتور ذخیره‌شده‌ای یافت نشد</div>';
    return;
  }
  list.forEach(inv => {
    const firstTitle = (inv.items && inv.items[0] && inv.items[0].title) || 'فاکتور';
    const numLabel = inv.invoiceNumber ? ('#' + toFaDigits(inv.invoiceNumber) + ' — ') : '';
    const row = document.createElement('div');
    row.className = 'saved-row';
    row.innerHTML = `
      <div class="saved-info">${numLabel}${firstTitle} — ${inv.dateIssue || ''}</div>
      <div class="saved-actions">
        <button class="btn-ghost btn-sm" data-id="${inv.id}" data-act="load">بارگذاری</button>
        <button class="btn-ghost btn-sm" data-id="${inv.id}" data-act="dup">کپی</button>
        <button class="btn-danger btn-sm" data-id="${inv.id}" data-act="del">حذف</button>
      </div>`;
    listEl.appendChild(row);
  });
}
document.getElementById('btnLoadList').addEventListener('click', () => {
  const bar = document.getElementById('savedBar');
  bar.classList.toggle('show');
  if(bar.classList.contains('show')) refreshSavedList();
});
document.getElementById('savedSearch').addEventListener('input', () => {
  const q = toEnDigits(document.getElementById('savedSearch').value).toLowerCase();
  const list = (window._loadedInvoices || []).filter(inv => {
    const hay = toEnDigits([
      inv.companyName, inv.customerName, inv.dateIssue, inv.paymentId,
      (inv.items && inv.items[0] && inv.items[0].title) || ''
    ].join(' ')).toLowerCase();
    return hay.includes(q);
  });
  renderSavedList(list);
});
document.getElementById('savedList').addEventListener('click', async (e) => {
  const btn = e.target.closest('button[data-act]');
  if(!btn) return;
  const id = btn.dataset.id;
  const inv = (window._loadedInvoices || []).find(i => i.id === id);
  if(btn.dataset.act === 'load'){
    if(inv){ loadPayload(inv); showToast('فاکتور بارگذاری شد'); }
  } else if(btn.dataset.act === 'dup'){
    if(inv){
      const copy = JSON.parse(JSON.stringify(inv));
      copy.id = null;
      copy.invoiceNumber = null;
      copy.paidTotal = 0;
      copy.paymentId = '';
      copy.statusOverride = 'auto';
      loadPayload(copy);
      showToast('نسخه کپی آماده شد — برای ثبت آن را ذخیره کنید');
    }
  } else if(btn.dataset.act === 'del'){
    if(!confirm('این فاکتور برای همیشه حذف شود؟')) return;
    try{
      const res = await fetch('?', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + encodeURIComponent(id)
      });
      const data = await res.json();
      if(data.ok){ showToast('فاکتور حذف شد'); refreshSavedList(); }
      else { showToast('حذف ناموفق بود'); }
    }catch(err){ showToast('خطا در حذف'); }
  }
});
// -------- مقداردهی اولیه (بدون داده نمونه) --------
document.getElementById('taxPct').value = toFaDigits(document.getElementById('taxPct').value);
document.getElementById('discountPct').value = toFaDigits(document.getElementById('discountPct').value);
updateCurrencyLabels();
updateDraftWatermark();
recalc();
</script>
</body>
</html>

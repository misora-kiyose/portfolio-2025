<?php
ini_set('display_errors','1');
error_reporting(E_ALL);
mb_language('Japanese');
mb_internal_encoding('UTF-8');

// ===== 設定（ここだけ自分の環境に合わせて） =====
$ADMIN_TO  = 'misor.a@hotmail.com';                 // 管理者が受け取る
$FROM_ADDR = 'no-reply@sorawebworks.stars.ne.jp';   // サーバードメインの送信元
$ALLOWED_SUBJECTS = ['商品について','ご注文について','その他'];

// ===== 便利関数 =====
function safe_text(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }
function has_newline(string $v): bool { return (bool)preg_match('/[\r\n]/', $v); }

// ===== 本処理 =====
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Location: /nhp1/contact.html'); exit;
}

$name    = safe_text($_POST['name']    ?? '');
$email   = safe_text($_POST['email']   ?? '');
$subject = safe_text($_POST['subject'] ?? '');
$message = safe_text($_POST['message'] ?? '');

if (!$name || !$email || !$subject || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  echo '必須項目が未入力か、メール形式が不正です。'; exit;
}
if (has_newline($name) || has_newline($email) || has_newline($subject)) {
  echo '不正な入力が検出されました。'; exit;
}
if (!in_array($subject, $ALLOWED_SUBJECTS, true)) { $subject = 'その他'; }

$from_name = mb_encode_mimeheader('和む ～心に残る和菓子～', 'UTF-8');

// --- 管理者宛 ---
$admin_subject = "【お問い合わせ】{$subject}";
$admin_body =
  "お名前: {$name}\n".
  "メール: {$email}\n".
  "件名: {$subject}\n\n".
  "----- お問い合わせ内容 -----\n{$message}\n";
$admin_headers = "From: {$from_name} <{$FROM_ADDR}>\r\nReply-To: {$email}\r\n";
$admin_params  = '-f '.$FROM_ADDR;  // Return-Path（到達率UP）
$admin_ok = mb_send_mail($ADMIN_TO, $admin_subject, $admin_body, $admin_headers, $admin_params);

// --- 自動返信（ユーザー宛） ---
$user_subject = '【自動返信】お問い合わせありがとうございます';
$user_body =
  "{$name} 様\n\n".
  "このたびは「和む ～心に残る和菓子～」へお問い合わせいただき、ありがとうございます。\n".
  "以下の内容で受け付けました。通常 1〜2 営業日以内にご連絡いたします。\n\n".
  "───────────────\n".
  "件名: {$subject}\n".
  "内容:\n{$message}\n".
  "───────────────\n\n".
  "※このメールは送信専用です。返信されてもご対応できません。\n".
  "ご用件がある場合はサイトのお問い合わせフォームからお願いいたします。\n";
$user_headers = "From: {$from_name} <{$FROM_ADDR}>\r\n";
$user_params  = '-f '.$FROM_ADDR;
$user_ok = mb_send_mail($email, $user_subject, $user_body, $user_headers, $user_params);

// --- 完了 ---
if ($admin_ok && $user_ok) { header('Location: /nhp1/thanks.html'); exit; }
echo '送信に失敗しました。時間をおいて再度お試しください。'; exit;

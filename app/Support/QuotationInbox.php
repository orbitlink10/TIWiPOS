<?php
namespace App\Support;
use RuntimeException;
class QuotationInbox
{
    public function read(array $settings, ?int $uid = null): array
    {
        if (!function_exists('imap_open')) { throw new RuntimeException('Enable the PHP IMAP extension on your hosting account to read incoming emails.'); }
        imap_timeout(IMAP_OPENTIMEOUT, 15);
        imap_timeout(IMAP_READTIMEOUT, 15);
        $connection = @imap_open('{'.$settings['host'].':993/imap/ssl/validate-cert/norsh}INBOX', $settings['username'], $settings['password'], OP_READONLY, 1, ['DISABLE_AUTHENTICATOR' => 'GSSAPI']);
        if (!$connection) {
            $errors = imap_errors() ?: [];
            imap_alerts();
            throw new RuntimeException($this->connectionError($errors));
        }
        try {
            if ($uid !== null) {
                $headers = imap_fetch_overview($connection, (string) $uid, FT_UID);
                if (!$headers) { return ['messages' => [], 'message' => null]; }
                $structure = imap_fetchstructure($connection, $uid, FT_UID);
                $message = $this->summary($headers[0]);
                $message['body'] = $structure ? $this->body($connection, $uid, $structure) : 'Message body unavailable.';
                return ['messages' => [], 'message' => $message];
            }
            $count = imap_num_msg($connection);
            $headers = $count ? imap_fetch_overview($connection, max(1, $count - 49).':'.$count, 0) : [];
            return ['messages' => array_map(fn ($header) => $this->summary($header), array_reverse($headers ?: [])), 'message' => null];
        } finally {
            imap_close($connection);
            imap_errors(); imap_alerts();
        }
    }

    public function connectionError(array $errors): string
    {
        // Classify errors rather than exposing raw server responses or credentials.
        $error = strtolower(implode(' ', $errors));
        if (preg_match('/certificate|peer name|self.signed|unable to get.*issuer/', $error)) {
            return 'The incoming mail server SSL certificate could not be verified. In cPanel > Email Accounts > Connect Devices, copy the Incoming Server from Secure SSL/TLS Settings into the POS IMAP Host. Your host may use its server hostname instead of mail.yourdomain. If it still fails, ask your hosting provider to check the mail certificate.';
        }
        if (preg_match('/authentication|authenticate|login failed|invalid credentials|authorization|password|login denied/', $error)) {
            return 'The mail server rejected the login. Use your full mailbox email address as the username and its mailbox password. Verify that the same details work in cPanel Webmail, then re-enter the password in Incoming Email settings.';
        }
        if (preg_match('/resolve|name or service|no such host|host not found|nodename|unknown host/', $error)) {
            return 'The incoming mail hostname could not be found. Copy the secure Incoming Server hostname from cPanel > Email Accounts > Connect Devices. Enter only the hostname, without https://, a port or a path.';
        }
        if (preg_match('/timed out|timeout|refused|unreachable|connect|network/', $error)) {
            return 'The POS could not reach the incoming mail server on IMAP port 993. Check the secure Incoming Server hostname in cPanel, then ask your hosting provider to confirm that IMAP is enabled and connections from this website to port 993 are allowed.';
        }
        return 'Could not open the inbox. Check the secure Incoming Server hostname and mailbox login in cPanel > Email Accounts > Connect Devices. If they are correct, ask your hosting provider to check IMAP access on port 993.';
    }

    private function summary(object $header): array
    {
        return ['uid' => (int) $header->uid, 'subject' => mb_decode_mimeheader($header->subject ?? '(No subject)'),
            'from' => mb_decode_mimeheader($header->from ?? ''), 'date' => $header->date ?? '', 'unread' => empty($header->seen)];
    }

    private function body($connection, int $uid, object $part, string $section = '', int $depth = 0): string
    {
        if ($depth > 15 || strtoupper($part->disposition ?? '') === 'ATTACHMENT') { return ''; }
        if (($part->type ?? 0) === 1) {
            $bodies = [];
            foreach ($part->parts ?? [] as $index => $child) {
                $text = $this->body($connection, $uid, $child, $section === '' ? (string) ($index + 1) : $section.'.'.($index + 1), $depth + 1);
                if ($text !== '') { $bodies[] = $text; }
                if (strtoupper($part->subtype ?? '') === 'ALTERNATIVE' && $text !== '') { break; }
            }
            return implode("\n\n", $bodies);
        }
        if (($part->type ?? 0) !== 0 || !in_array(strtoupper($part->subtype ?? 'PLAIN'), ['PLAIN', 'HTML'])) { return ''; }
        if (($part->bytes ?? 0) > 1024 * 1024) { return '[Message body exceeds the 1 MB preview limit. Open it in webmail.]'; }
        $text = $section === '' ? imap_body($connection, $uid, FT_UID | FT_PEEK) : imap_fetchbody($connection, $uid, $section, FT_UID | FT_PEEK);
        $text = match ((int) ($part->encoding ?? 0)) { 3 => base64_decode($text ?: '', true) ?: '', 4 => quoted_printable_decode($text ?: ''), default => $text ?: '' };
        foreach ($part->parameters ?? [] as $parameter) {
            if (strtolower($parameter->attribute) === 'charset' && in_array(strtoupper($parameter->value), array_map('strtoupper', mb_list_encodings()), true)) {
                $text = mb_convert_encoding($text, 'UTF-8', $parameter->value);
            }
        }
        if (strtoupper($part->subtype ?? '') === 'HTML') {
            $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $text);
            $text = html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return trim($text);
    }
}

<?php
declare(strict_types=1);

class Email
{
    private string $from;
    private string $password;
    private string $fromName;
    private string $appUrl;

    public function __construct()
    {
        $this->from = $_ENV['EMAIL'] ?? '';
        $this->password = $_ENV['EMAIL_PASSWORD'] ?? '';
        $this->fromName = $_ENV['EMAIL_FROM_NAME'] ?? 'API';
        $this->appUrl = $_ENV['APP_URL'] ?? '';
    }

    public function send(string $to, string $subject, string $message): array
    {
        if (empty($this->from) || empty($this->password)) {
            return ['status' => 'FAILED', 'error' => 'Email not configured'];
        }

        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'FAILED', 'error' => 'Invalid email'];
        }

        return $this->sendWithSmtp($to, $subject, $message);
    }

    private function sendWithSmtp(string $to, string $subject, string $message): array
    {
        $socket = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 10);
        
        if (!$socket) {
            return ['status' => 'FAILED', 'error' => "Connection failed: $errstr"];
        }

        $response = $this->smtpRead($socket);
        
        // EHLO
        $this->smtpWrite($socket, "EHLO localhost\r\n");
        $this->smtpRead($socket);

        // AUTH LOGIN
        $this->smtpWrite($socket, "AUTH LOGIN\r\n");
        $this->smtpRead($socket);

        // Username (base64)
        $this->smtpWrite($socket, base64_encode($this->from) . "\r\n");
        $this->smtpRead($socket);

        // Password (base64)
        $this->smtpWrite($socket, base64_encode($this->password) . "\r\n");
        $authResponse = $this->smtpRead($socket);

        if (strpos($authResponse, '235') === false) {
            fclose($socket);
            return ['status' => 'FAILED', 'error' => 'Authentication failed'];
        }

        // MAIL FROM
        $this->smtpWrite($socket, "MAIL FROM:<{$this->from}>\r\n");
        $this->smtpRead($socket);

        // RCPT TO
        $this->smtpWrite($socket, "RCPT TO:<{$to}>\r\n");
        $this->smtpRead($socket);

        // DATA
        $this->smtpWrite($socket, "DATA\r\n");
        $this->smtpRead($socket);

        // Email content
        $emailContent = "From: {$this->fromName} <{$this->from}>\r\n";
        $emailContent .= "To: {$to}\r\n";
        $emailContent .= "Subject: {$subject}\r\n";
        $emailContent .= "MIME-Version: 1.0\r\n";
        $emailContent .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $emailContent .= "\r\n";
        $emailContent .= $message;
        $emailContent .= "\r\n.\r\n";

        $this->smtpWrite($socket, $emailContent);
        $dataResponse = $this->smtpRead($socket);

        // QUIT
        $this->smtpWrite($socket, "QUIT\r\n");
        fclose($socket);

        if (strpos($dataResponse, '250') !== false) {
            return ['status' => 'SUCCESS', 'msg' => 'Email sent'];
        }

        return ['status' => 'FAILED', 'error' => 'Failed to send email'];
    }

    private function smtpWrite($socket, string $data): void
    {
        fwrite($socket, $data);
    }

    private function smtpRead($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }

    public function sendVerificationEmail(string $to, string $username, string $token): array
    {
        $verifyUrl = $this->appUrl . '/verify?token=' . $token;
        
        $subject = "Verify Your Email";
        $message = "Hi {$username},\n\n";
        $message .= "Click this link to verify your email:\n\n";
        $message .= "{$verifyUrl}\n\n";
        $message .= "Or use this token: {$token}\n\n";
        $message .= "This link expires in 24 hours.";

        return $this->send($to, $subject, $message);
    }
}

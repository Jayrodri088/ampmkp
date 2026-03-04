<?php

/**
 * Bot Protection System
 * Provides anti-bot protection without captcha using various techniques:
 * - Rate limiting (2 submissions per day per email/IP)
 * - Suspicious email pattern detection
 * - Honeypot fields
 * - Timing analysis
 * - User agent validation
 */
class BotProtection {
    
    private $dataDir;
    private $logFile;
    private $submissionsFile;
    
    // Rate limiting settings
    private $maxSubmissionsPerDay = 2;
    private $minSubmissionTime = 3; // seconds
    private $maxSubmissionTime = 3600; // 1 hour
    
    // Suspicious email patterns
    private $suspiciousPatterns = [
        '/^[a-z]+\d+@/',  // letters followed by numbers
        '/^\d+[a-z]+@/',  // numbers followed by letters
        '/^[a-z]{1,3}@/', // very short usernames
        '/\+.*\+/',       // multiple plus signs
        '/\.{2,}/',       // multiple dots
        '/@.*\.tk$/',     // .tk domains
        '/@.*\.ml$/',     // .ml domains
        '/@.*\.ga$/',     // .ga domains
        '/@.*\.cf$/',     // .cf domains
        '/temp.*mail/',   // temporary email services
        '/10.*minute/',   // 10 minute mail
        '/guerrilla/',    // guerrilla mail
        '/mailinator/',   // mailinator
        '/zaim-fin\.com$/', // specific domain mentioned by user
    ];
    
    // Suspicious domains
    private $suspiciousDomains = [
        'zaim-fin.com',
        'tempmail.org',
        '10minutemail.com',
        'guerrillamail.com',
        'mailinator.com',
        'yopmail.com',
        'temp-mail.org',
        'throwaway.email',
        'imfger.co',
        'aggregator.top',
        'business-portal.ru',
        'contract-market.com'
    ];

    // Spam content patterns
    private $spamPatterns = [
        '/aggregator\.top/i',
        '/\u0410\u0433\u0440\u0435\u0433\u0430\u0442\u043e\u0440/u', // Russian "Aggregator"
        '/\u0431\u0438\u0437\u043d\u0435\u0441/u', // Russian "business"
        '/\u043a\u043e\u043d\u0442\u0440\u0430\u043a\u0442/u', // Russian "contract"
        '/\u0441\u0434\u0435\u043b\u043a/u', // Russian "deals"
        '/\u0431\u0430\u043d\u043a/u', // Russian "bank"
        '/\u043a\u0440\u0435\u0434\u0438\u0442/u', // Russian "credit"
        '/\u043b\u0438\u0437\u0438\u043d\u0433/u', // Russian "leasing"
        '/https?:\/\/[^\s]+/i', // URLs in messages
        '/[a-z]{15,}/i', // Long strings of random letters
        '/[bcdfghjklmnpqrstvwxyz]{8,}/i', // Long consonant strings (gibberish)
        '/\b(loan|credit|business|contract|deal|bank|finance|investment)\b/i',
        '/\b(casino|gambling|poker|bet|win money)\b/i',
        '/\b(viagra|cialis|pharmacy|pills|medication)\b/i',
        '/\b(make money|earn money|work from home|get rich)\b/i'
    ];
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../data/';
        $this->logFile = $this->dataDir . 'bot_protection_logs.json';
        $this->submissionsFile = $this->dataDir . 'form_submissions.json';
        
        // Create data directory if it doesn't exist
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    /**
     * Validate form submission against bot protection rules
     */
    public function validateSubmission($formType, $postData) {
        $errors = [];
        $clientIP = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $email = $postData['email'] ?? '';
        
        // Check rate limiting (per form type: login allows more attempts)
        if (!$this->checkRateLimit($email, $clientIP, $formType)) {
            $errors[] = 'Rate limit exceeded';
        }
        
        // Check suspicious email patterns
        if (!$this->validateEmail($email)) {
            $errors[] = 'Suspicious email pattern detected';
        }
        
        // Check honeypot field (if present)
        if (!$this->checkHoneypot($postData)) {
            $errors[] = 'Honeypot field filled';
        }
        
        // Check submission timing
        if (!$this->checkSubmissionTiming($postData)) {
            $errors[] = 'Suspicious submission timing';
        }
        
        // Check user agent
        if (!$this->validateUserAgent($userAgent)) {
            $errors[] = 'Invalid user agent';
        }

        // Check message content for spam
        if (!$this->validateMessageContent($postData)) {
            $errors[] = 'Spam content detected';
        }

        // Log submission attempt
        $this->logSubmission($formType, $email, $clientIP, $userAgent, $errors);
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check rate limiting per form type. Default max 2/day per email or IP.
     * Customer login gets a higher limit (10/day per email) so users can request codes a few times.
     */
    private function checkRateLimit($email, $ip, $formType = '') {
        $submissions = $this->getSubmissions();
        $today = date('Y-m-d');
        $count = 0;
        $maxAllowed = ($formType === 'customer_login') ? 10 : $this->maxSubmissionsPerDay;

        foreach ($submissions as $submission) {
            if (date('Y-m-d', strtotime($submission['timestamp'])) === $today
                && ($submission['form_type'] ?? '') === $formType) {
                if ($submission['email'] === $email || $submission['ip'] === $ip) {
                    $count++;
                }
            }
        }

        return $count < $maxAllowed;
    }
    
    /**
     * Validate email against suspicious patterns
     */
    private function validateEmail($email) {
        if (empty($email)) {
            return false;
        }
        
        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Check against suspicious patterns
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, strtolower($email))) {
                return false;
            }
        }
        
        // Check against suspicious domains
        $domain = strtolower(substr(strrchr($email, "@"), 1));
        if (in_array($domain, $this->suspiciousDomains)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check honeypot field (should be empty)
     */
    private function checkHoneypot($postData) {
        // Check for common honeypot field names
        $honeypotFields = ['website', 'url', 'homepage', 'bot_field', 'spam_check'];
        
        foreach ($honeypotFields as $field) {
            if (isset($postData[$field]) && !empty($postData[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check submission timing (too fast = bot, too slow = suspicious)
     */
    private function checkSubmissionTiming($postData) {
        $formStartTime = $postData['form_start_time'] ?? null;
        
        if (!$formStartTime) {
            return true; // No timing data available
        }
        
        $submissionTime = time() - (int)$formStartTime;
        
        return $submissionTime >= $this->minSubmissionTime && 
               $submissionTime <= $this->maxSubmissionTime;
    }
    
    /**
     * Validate user agent
     */
    private function validateUserAgent($userAgent) {
        if (empty($userAgent)) {
            return false;
        }

        // Check for common bot patterns
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
            '/php/i'
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate message content for spam patterns
     */
    private function validateMessageContent($postData) {
        $message = $postData['message'] ?? '';
        $name = $postData['name'] ?? '';

        if (empty($message)) {
            return true; // No message to validate
        }

        // Check against spam patterns
        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $message) || preg_match($pattern, $name)) {
                return false;
            }
        }

        // Check for excessive gibberish (long strings of consonants)
        if (preg_match('/[bcdfghjklmnpqrstvwxyz]{10,}/i', $message)) {
            return false;
        }

        // Check for excessive random characters
        if (preg_match('/[a-z]{20,}/i', $message) && !preg_match('/[aeiou]/i', $message)) {
            return false;
        }

        // Check message length vs meaningful content ratio
        $messageLength = strlen($message);
        if ($messageLength > 100) {
            $vowelCount = preg_match_all('/[aeiou]/i', $message);
            $consonantCount = preg_match_all('/[bcdfghjklmnpqrstvwxyz]/i', $message);

            // If consonant to vowel ratio is too high, likely gibberish
            if ($vowelCount > 0 && ($consonantCount / $vowelCount) > 4) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Log submission attempt
     */
    private function logSubmission($formType, $email, $ip, $userAgent, $errors) {
        $submissions = $this->getSubmissions();
        
        $submission = [
            'timestamp' => date('Y-m-d H:i:s'),
            'form_type' => $formType,
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'errors' => $errors,
            'blocked' => !empty($errors)
        ];
        
        $submissions[] = $submission;
        
        // Keep only last 1000 submissions
        if (count($submissions) > 1000) {
            $submissions = array_slice($submissions, -1000);
        }
        
        file_put_contents($this->submissionsFile, json_encode($submissions, JSON_PRETTY_PRINT));
    }
    
    /**
     * Log suspicious activity
     */
    public function logSuspiciousActivity($formType, $postData, $errors) {
        $logs = $this->getLogs();
        
        $log = [
            'timestamp' => date('Y-m-d H:i:s'),
            'form_type' => $formType,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'post_data' => $postData,
            'errors' => $errors
        ];
        
        $logs[] = $log;
        
        // Keep only last 500 logs
        if (count($logs) > 500) {
            $logs = array_slice($logs, -500);
        }
        
        file_put_contents($this->logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get submissions data
     */
    private function getSubmissions() {
        if (!file_exists($this->submissionsFile)) {
            return [];
        }
        
        $content = file_get_contents($this->submissionsFile);
        return json_decode($content, true) ?: [];
    }
    
    /**
     * Get logs data
     */
    private function getLogs() {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $content = file_get_contents($this->logFile);
        return json_decode($content, true) ?: [];
    }
}

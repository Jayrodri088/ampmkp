<?php
/**
 * IDE stub only — do not require this file.
 * PHPMailer is loaded at runtime via Composer (vendor) or lib/PHPMailer.
 * This file exists so the language server can resolve the type.
 */
namespace PHPMailer\PHPMailer {

    class PHPMailer
    {
        /** @var string */
        public $Host;
        /** @var bool */
        public $SMTPAuth;
        /** @var string */
        public $Username;
        /** @var string */
        public $Password;
        /** @var string */
        public $SMTPSecure;
        /** @var int */
        public $Port;
        /** @var string */
        public $Sender;
        /** @var string */
        public $Subject;
        /** @var string */
        public $Body;
        /** @var string */
        public $AltBody;

        public function __construct($exceptions = null) {}
        public function isSMTP() {}
        public function isHTML($isHtml = true) {}
        public function setFrom($address, $name = '', $auto = true) {}
        public function addAddress($address, $name = '') {}
        public function addReplyTo($address, $name = '') {}
        public function addStringAttachment($string, $filename, $encoding = 'base64', $type = '') {}
        public function send() {}
        public function clearAddresses() {}
        public function clearReplyTos() {}
    }
}

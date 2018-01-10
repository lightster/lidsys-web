<?php

namespace Lidsys\Application\Service;

use Mailgun\Mailgun;

class MailerService
{
    private $key;
    private $domain;
    private $api_endpoint;
    private $api_ssl;

    private $substitutions;
    private $defaults;

    private $mailgun;

    public function __construct($key, $domain, array $options = [])
    {
        $substitutions = $defaults = $overrides = [];
        $api_endpoint = 'api.mailgun.net';
        $api_ssl = true;
        extract($options, EXTR_IF_EXISTS);

        $this->key           = $key;
        $this->domain        = $domain;
        $this->api_endpoint  = $api_endpoint;
        $this->api_ssl       = $api_ssl;

        $this->substitutions = $substitutions;
        $this->defaults      = $defaults;
        $this->overrides     = $overrides;
    }

    private function getMailgun()
    {
        if ($this->mailgun) {
            return $this->mailgun;
        }

        $this->mailgun = new Mailgun(
            $this->key,
            $this->api_endpoint,
            'v2',
            $this->api_ssl
        );

        return $this->mailgun;
    }

    public function sendMessage(array $data, array $local_subs = [])
    {
        $data = array_replace_recursive(
            $this->defaults,
            $data,
            $this->overrides
        );

        $substitutions = array_replace_recursive(
            $this->substitutions,
            $local_subs
        );

        $this->substituteString($data, 'text', $substitutions);
        $this->substituteString($data, 'html', $substitutions);

        return $this->getMailgun()->sendMessage($this->domain, $data);
    }

    private function substituteString(array & $data, $field, $substitutions)
    {
        if (!empty($data[$field])) {
            $data[$field] = str_replace(
                array_keys($substitutions),
                array_values($substitutions),
                $data[$field]
            );
        }
    }
}

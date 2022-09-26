<?php
require_once(__DIR__.'/spyc-0.5/spyc.php');
$WEBHOOK_URL = empty(getenv('SLACK_WEB_HOOK')) ? '' : getenv('SLACK_WEB_HOOK');
$HOST= empty(getenv('HOST')) ? '' : getenv('HOST');
$LIMIT = empty(getenv('LIMIT_DAY')) ? 30 : getenv('LIMIT_DAY');
$STAGE = empty(getenv('STAGE')) ? 'staging' : getenv('STAGE');
if($STAGE !== "production"){
  $LIMIT = 365;
}

if($WEBHOOK_URL === "" || $HOST === ""){
  // TODO validation
  echo '"SLACK_WEB_HOOK" or "HOST" are not set correctly.',PHP_EOL;
  return;
}

$yml= Spyc::YAMLLoad(__DIR__."/_ssl.log");

class SlackNotifier
{
    /** @var string */
    private $webhookUrl;
    /** @var int */
    private $timeout;

    /**
     * @param string $webhookUrl
     * @param int $timeout
     */
    public function __construct($webhookUrl = '', $timeout = 3)
    {
        $this->webhookUrl = $webhookUrl;
        $this->timeout = $timeout;
    }

    /**
     * @param string $text
     * @param array $options
     * @return array
     */
    public function text($text, array $options = [])
    {
        return $this->send(array_merge($options, [
            'text' => $text,
        ]));
    }

    /**
     * @param array $attachment
     * @param array $options
     * @return array
     */
    public function attachment(array $attachment, array $options = [])
    {
        return $this->send(array_merge($options, [
            'text'        => '',
            'attachments' => [$attachment],
        ]));
    }

    /**
     * @param Exception $exception
     * @param array $meta
     * @param array $options
     * @return array
     */
    public function exception(\Exception $exception, array $meta = [], array $options = [])
    {
        return $this->attachment([
            'color'  => 'danger',
            'title'  => sprintf('[%s] %s', get_class($exception), $exception->getMessage()),
            'fields' => array_map(function ($title, $value) {
                return [
                    'title' => $title,
                    'value' => $value,
                    'short' => true,
                ];
            }, array_keys($meta), array_values($meta))
        ], $options);
    }

    /**
     * @param array $payload
     * @return array
     */
    public function send(array $payload)
    {
        return $this->httpPost(
            $this->webhookUrl,
            ['payload' => json_encode(array_filter($payload))]
        );
    }

    /**
     * @param string $url
     * @param array $data
     * @return array
     */
    private function httpPost($url, $data)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        curl_close($curl);
        return [
            'url'    => $url,
            'data'   => $data,
            'status' => $status,
            'header' => $header,
            'body'   => $body,
        ];
    }
}

if(!empty($yml["Not After"])){
  $ssl_after_datetime = new Datetime($yml["Not After"]);
  $now = new Datetime();

  $diff = $ssl_after_datetime->diff($now);
  $after_days = $diff->format('%a');
  
  $msg = 'Host: ' . $HOST . PHP_EOL;

  if($after_days > $LIMIT){
    $msg .= 'There is plenty of time left on the node certificate expiration date.' . PHP_EOL;
    $msg .= 'Remaining expiration date: '. $after_days .' days'  . PHP_EOL;
    $msg .= '(' . $ssl_after_datetime->format('Y-m-d H:i:s') . ')' . PHP_EOL;
    echo $msg;
    return;
  }
  
  $msg .= 'Remaining expiration date: '. $after_days .' days' . PHP_EOL;
  $msg .= '(' . $ssl_after_datetime->format('Y-m-d H:i:s') . ')' . PHP_EOL;
  $msg .= 'External Links: <https://nemtus.com/bootstrap-renewcertificates|Update renewCertificates>' . PHP_EOL;

  $notifier = new SlackNotifier($WEBHOOK_URL);
  $notifier->exception(new Exception('Node Certificates'), [
    'Message'  => $msg,
  ]);
  echo 'send slack message.',PHP_EOL;
}else{
  echo 'Please try again in 1 minute.',PHP_EOL;
}

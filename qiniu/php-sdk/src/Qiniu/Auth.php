<?php
namespace App\Libraries\Qiniu;

use App\Libraries\Qiniu\functions;

final class Auth
{
    private $accessKey;
    private $secretKey;

    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    public function sign($data)
    {
        $hmac = hash_hmac('sha1', $data, $this->secretKey, true);
		new functions();
        return $this->accessKey . ':' . $this->base64_urlSafeEncode($hmac);
    }

    public function signWithData($data)
    {
        $data = $this->base64_urlSafeEncode($data);
        return $this->sign($data) . ':' . $data;
    }

    public function signRequest($urlString, $body, $contentType = null)
    {
        $url = parse_url($urlString);
        $data = '';
        if (isset($url['path'])) {
            $data = $url['path'];
        }
        if (isset($url['query'])) {
            $data .= '?' . $url['query'];
        }
        $data .= "\n";

        if ($body != null &&
            ($contentType == 'application/x-www-form-urlencoded') ||  $contentType == 'application/json') {
            $data .= $body;
        }
        return $this->sign($data);
    }

    public function verifyCallback($contentType, $originAuthorization, $url, $body)
    {
        $authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);
        return $originAuthorization === $authorization;
    }

    public function privateDownloadUrl($baseUrl, $expires = 3600)
    {
        $deadline = time() + $expires;

        $pos = strpos($baseUrl, '?');
        if ($pos !== false) {
            $baseUrl .= '&e=';
        } else {
            $baseUrl .= '?e=';
        }
        $baseUrl .= $deadline;

        $token = $this->sign($baseUrl);
        return "$baseUrl&token=$token";
    }

    public function uploadToken(
        $bucket,
        $key = null,
        $expires = 3600,
        $policy = null,
        $strictPolicy = true
    ) {
        $deadline = time() + $expires;
        $scope = $bucket;
        if ($key != null) {
            $scope .= ':' . $key;
        }
        $args = array();
        $args = self::copyPolicy($args, $policy, $strictPolicy);
        $args['scope'] = $scope;
        $args['deadline'] = $deadline;
        $b = json_encode($args);
        return $this->signWithData($b);
    }

    /**
     *上传策略，参数规格详见
     *http://developer.qiniu.com/docs/v6/api/reference/security/put-policy.html
     */
    private static $policyFields = array(
        'callbackUrl',
        'callbackBody',
        'callbackHost',
        'callbackBodyType',
        'callbackFetchKey',

        'returnUrl',
        'returnBody',

        'endUser',
        'saveKey',
        'insertOnly',

        'detectMime',
        'mimeLimit',
        'fsizeLimit',

        'persistentOps',
        'persistentNotifyUrl',
        'persistentPipeline',
    );

    private static $deprecatedPolicyFields = array(
        'asyncOps',
    );

    private static function copyPolicy(&$policy, $originPolicy, $strictPolicy)
    {
        if ($originPolicy == null) {
            return;
        }
        foreach ($originPolicy as $key => $value) {
            if (in_array($key, self::$deprecatedPolicyFields)) {
                throw new \InvalidArgumentException("{$key} has deprecated");
            }
            if (!$strictPolicy || in_array($key, self::$policyFields)) {
                $policy[$key] = $value;
            }
        }
        return $policy;
    }

    public function authorization($url, $body = null, $contentType = null)
    {
        $authorization = 'QBox ' . $this->signRequest($url, $body, $contentType);
        return array('Authorization' => $authorization);
    }


	/**
     * 对提供的数据进行urlsafe的base64编码。
     *
     * @param string $data 待编码的数据，一般为字符串
     *
     * @return string 编码后的字符串
     * @link http://developer.qiniu.com/docs/v6/api/overview/appendix.html#urlsafe-base64
     */
	public function base64_urlSafeEncode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }
}

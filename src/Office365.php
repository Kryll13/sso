<?php
/**
 * Class Office365 file
 *
 * @package    Kryll13 Thy repository name.
 * @subpackage SSO
 * @author     Philippe Mondou <kryll@free.fr
 * @copyright  CC-BY-NC-SA-4.0
 * @license    https://creativecommons.org/licenses CC-BY-NC-SA-4.0
 */

namespace Kryll13\SSO;

use Dotenv\Dotenv;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\User;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

/**
 * Class Office365
 *
 *   Manage Microsoft Office 365 Single Sign On with OpenID (OAuth2)
 */
class Office365
{
    /**
     * The OAuth2 client provider for Miccrosoft.
     *
     * @var Microsoft
     */
    private $client;

    /**
     * The Microsoft Graph managing object.
     *
     * @var Graph
     */
    private $graph;

    /**
     * Office365 constructor.
     *
     * @param string $envpath The environment file path.
     */
    public function __construct(string $envpath)
    {
        if (PHP_SESSION_NONE === session_status()) session_start();
        $env = Dotenv::create($envpath);
        $env->load();
        $this->graph  = new Graph();
        $this->client = new Microsoft(
            [
                'clientId'       => getenv('APP_ID'),
                'clientSecret'   => getenv('APP_SECRET'),
                'redirectUri'    => getenv('REDIRECT_URI'),
                'urlAuthorize'   => getenv('AUTHORITY_URL').'/'.getenv('TENANT_ID').getenv('AUTHORITY_ENDPOINT_PATH'),
                'urlAccessToken' => getenv('AUTHORITY_URL').'/'.getenv('TENANT_ID').getenv('AUTHORITY_TOKEN_PATH'),
            ]
        );
    }//end __construct()

    /**
     * Return true if user is logged.
     *
     * @return boolean
     */
    public function isLogged(): bool
    {
        return (true === isset($_SESSION['Office365AccessToken']));
    }//end isLogged()

    /**
     * Return true if connection is expired
     *
     * @return boolean
     */
    public function isExpired(): bool
    {
        if (true === $this->isLogged()) {
            if (($_SESSION['Office365AccessToken']['expires'] - 3300) < time()) {
                return true;
            }
        }
        return false;
    }//end isExpired()

    /**
     * If not logged redirect to the AuthorizationURL provided.
     *
     * @return void Nothing.
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException This could happen.
     */
    public function login(): void
    {
        if (false === $this->isLogged()) {
            if (false === isset($_GET['code'])) {
                $options = [
                    'scope' => getenv('SCOPES'),
                ];
                $authUrl = $this->client->getAuthorizationUrl($options);
                header('Location: '.$authUrl);
            } else {
                $this->getAccessToken();
            }
        } else {
            if (true === $this->isExpired()) {
                $this->renewAccessToken();
            }
        }
    }//end login()

    /**
     * If logged unset Session data and redirect to logout.
     *
     * @return void Nothing.
     */
    public function logout(): void
    {
        if (true === $this->isLogged()) {
            unset($_SESSION['Office365AccessToken']);
            $path  = getenv('AUTHORITY_URL');
            $path .= '/'.getenv('TENANT_ID');
            $path .= getenv('AUTHORITY_LOGOUT_PATH').urlencode(getenv('APP_URL'));
            header('Location: '.$path);
        }
    }//end logout()

    /**
     * Catch the code and store data into session
     *
     * @return void Nothing.
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException This could happen.
     */
    private function getAccessToken(): void
    {
        $accessToken = $this->client->getAccessToken(
            'authorization_code',
            [
                'code' => $_GET['code'],
            ]
        );
        $_SESSION['Office365AccessToken'] = [
            'token'        => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires'      => $accessToken->getExpires(),
        ];
        header('Location: '.getenv('APP_URL'));
    }//end getAccessToken()

    /**
     * Fetch a new data set when providing the refresh token.
     *
     * @return void Nothing.
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException This could happen.
     */
    private function renewAccessToken(): void
    {
        $accessToken = $this->client->getAccessToken(
            'refresh_token',
            [
                'refresh_token' => $_SESSION['Office365AccessToken']['refreshToken'],
            ]
        );
        $_SESSION['Office365AccessToken'] = [
            'token'        => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires'      => $accessToken->getExpires(),
        ];
        header('Location: '.getenv('APP_URL'));
    }//end renewAccessToken()

    /**
     * Get the logged user or not.
     *
     * @return User|null The logged user object or null.
     * @throws \Microsoft\Graph\Exception\GraphException This could happen.
     */
    public function getUser(): ?User
    {
        if (true === $this->isLogged()) {
            $this->graph->setAccessToken($_SESSION['Office365AccessToken']['token']);
            return $this->graph->createRequest('GET', '/me')->setReturnType(User::class)->execute();
        }
        return null;
    }//end getUser()
}//end class

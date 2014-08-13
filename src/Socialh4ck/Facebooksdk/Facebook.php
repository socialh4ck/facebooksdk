<?php namespace Socialh4ck\Facebooksdk;

use Facebook\GraphUser;
use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Facebook\FacebookRedirectLoginHelper;

/**
 * Class Facebook
 * @package Socialh4ck\Facebook
 */
class Facebook {
	
    /**
     * @var App Id
     */
    protected $appId;
    
	/**
     * @var App Secret
     */
    protected $appSecret;
	
	/**
     * @var Redirector
     */
    protected $redirect_url;
	
    /**
     * @param Store $session
     * @param Redirector $redirect
     * @param Repository $config
     * @param null $appId
     * @param null $appSecret
     */
    public function __construct($appId = null, $appSecret = null, $redirect_url = null)
	{
		session_start();
		
		$this->appId        = $appId;
        $this->appSecret    = $appSecret;
		$this->redirect_url = $redirect_url;
		
        FacebookSession::setDefaultApplication($appId, $appSecret);

    }

    /**
     * Get redirect url.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirect_url ?: \Config::get('facebook::redirect_url', '/');
    }

    /**
     * Set new redirect url.
     * 
     * @param $this $url 
     */
    public function setRedirectUrl($url)
    {
    	$this->redirect_url = $url;

    	return $this;
    }

    /**
     * Get Facebook Redirect Login Helper.
     *
     * @return FacebookRedirectLoginHelper
     */
    public function getFacebookHelper()
    {
        $appId     = $this->appId     ?: \Config::get('facebook::app_id');
        $appSecret = $this->appSecret ?: \Config::get('facebook::app_secret');
		
        return new FacebookRedirectLoginHelper($this->getRedirectUrl(), $appId, $appSecret);
    }

	/**
	 * Get AppId.
	 * 
	 * @return string 
	 */
	public function getAppId()
	{
		return $this->appId;
	}

	/**
	 * Get scope.
	 * 
	 * @param  array  $merge 
	 * @return string|mixed        
	 */
	protected function getScope($merge = array())
	{
		if(count($merge) > 0) return $merge;

		return \Config::get('facebook::scope');
	}

    /**
     * Get Login Url.
     *
     * @param array $scope
     * @param null $version
     * @return string
     */
	public function getLoginUrl($scope = array(), $version = null)
	{
		$scope = $this->getScope($scope);

		return $this->getFacebookHelper()->getLoginUrl($scope, $version);
	}

    /**
     * Redirect to the facebook login url.
     *
     * @param array $scope
     * @param null $version
     * @return Response
     */
	public function authenticate($scope = array(), $version = null)
	{
		return \Redirect::to($this->getLoginUrl($scope, $version));
	}

	/**
	 * Get the facebook session (access token) when redirected back.
	 * 
	 * @return mixed 
	 */
	public function getSessionFromRedirect()
	{
		$session = $this->getFacebookHelper()->getSessionFromRedirect();
	  	
	  	\Session::put('facebook.session', $session);
	  	
	  	return $session;
	}

	/**
	 * Get token when redirected back from facebook.
	 * 
	 * @return string 
	 */
	public function getTokenFromRedirect()
	{
		$session = $this->getSessionFromRedirect();

		return $session ? $session->getToken() : null;
	}

	/**
	 * Determine whether the "facebook.access_token".
	 * 
	 * @return boolean
	 */
	public function hasSessionToken()
	{
		return \Session::has('facebook.access_token');
	}

	/**
	 * Get the facebook access token via Session laravel.
	 * 
	 * @return string 
	 */
	public function getSessionToken()
	{
		return \Session::get('facebook.access_token');
	}

	/**
	 * Put the access token to the laravel session manager.
	 * 
	 * @param  string $token 
	 * @return void        
	 */
	public function putSessionToken($token)
	{
		\Session::put('facebook.access_token', $token);
	}

	/**
	 * Get the access token. If the current access token from session manager exists,
	 * then we will use them, otherwise we get from redirected facebook login.
	 * 
	 * @return mixed 
	 */
	public function getAccessToken()
	{
		if($this->hasSessionToken()) return $this->getSessionToken();

		return $this->getTokenFromRedirect();
	}

	/**
	 * Get callback from facebook.
	 * 
	 * @return boolean 
	 */
	public function getCallback()
	{
		$token = $this->getAccessToken();
		if( ! empty($token) )
		{
			$this->putSessionToken($token);
			return true;
		}
		return false;
	}

	/**
	 * Get facebook session from laravel session manager.
	 * 
	 * @return string|mixed 
	 */
	public function getFacebookSession()
	{
		return \Session::get('facebook.session');
	}

	/**
	 * Destroy all facebook session.
	 * 
	 * @return void 
	 */
	public function destroy()
	{
		\Session::forget('facebook.session');
		\Session::forget('facebook.access_token');
	}

	/**
	 * Logout the current user.
	 * 
	 * @return void
	 */
	public function logout()
	{
	 	$this->destroy();
	}

	/**
	 * Facebook API Call.
	 * 
	 * @param  string $method     The request method.
	 * @param  string $path       The end points path.
	 * @param  mixed  $parameters Parameters.
	 * @param  string $version    The specified version of Api.
	 * @param  mixed  $etag
	 * @return mixed
	 */
	public function api($method, $path, $parameters = null, $version = null, $etag = null)
	{
		$session = $this->getFacebookSession();	
		
		if(empty($session) && !empty($parameters))
		{
			$session = new FacebookSession( $parameters['access_token'] );
		}
		
		$request = with(new FacebookRequest($session, $method, $path, $parameters, $version, $etag))
			->execute()
			->getGraphObject()->asArray();
		
		return $request;
	}

	/**
	 * Facebook API Request with "GET" method.
	 * 
	 * @param  string $path       
	 * @param  string|null|mixed $parameters 
	 * @param  string|null|mixed $version    
	 * @param  string|null|mixed $etag       
	 * @return mixed             
	 */
	public function get($path, $parameters = null, $version = null, $etag = null)
	{
		return $this->api('GET', $path, $parameters, $version, $etag);
	}

	/**
	 * Facebook API Request with "POST" method.
	 * 
	 * @param  string $path       
	 * @param  string|null|mixed $parameters 
	 * @param  string|null|mixed $version    
	 * @param  string|null|mixed $etag       
	 * @return mixed             
	 */
	public function post($path, $parameters = null, $version = null, $etag = null)
	{
		return $this->api('POST', $path, $parameters, $version, $etag);
	}

	/**
	 * Facebook API Request with "DELETE" method.
	 * 
	 * @param  string $path       
	 * @param  string|null|mixed $parameters 
	 * @param  string|null|mixed $version    
	 * @param  string|null|mixed $etag       
	 * @return mixed             
	 */
	public function delete($path, $parameters = null, $version = null, $etag = null)
	{
		return $this->api('DELETE', $path, $parameters, $version, $etag);
	}

	/**
	 * Facebook API Request with "PUT" method.
	 * 
	 * @param  string $path       
	 * @param  string|null|mixed $parameters 
	 * @param  string|null|mixed $version    
	 * @param  string|null|mixed $etag       
	 * @return mixed             
	 */
	public function put($path, $parameters = null, $version = null, $etag = null)
	{
		return $this->api('PUT', $path, $parameters, $version, $etag);
	}

	/**
	 * Facebook API Request with "PATCH" method.
	 * 
	 * @param  string $path       
	 * @param  string|null|mixed $parameters 
	 * @param  string|null|mixed $version    
	 * @param  string|null|mixed $etag       
	 * @return mixed             
	 */
	public function patch($path, $parameters = null, $version = null, $etag = null)
	{
		return $this->api('PATCH', $path, $parameters, $version, $etag);
	}

	/**
	 * Get user profile.
	 * 
	 * @return mixed 
	 */
	public function getProfile()
	{
		return $this->get('/me');
	}
	
	public function storeState($state)
	{
		\Session::put('state', $state);
	}

	public function loadState()
	{
		return $this->state = \Session::get('state');
	}
	
}

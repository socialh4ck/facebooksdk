<?php namespace Socialh4ck\Facebooksdk;

use Illuminate\Support\ServiceProvider;

class FacebookServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Boot the package.
	 * 
	 * @return void 
	 */
	public function boot()
	{
		$this->package('socialh4ck/facebook');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['facebook'] = $this->app->share(function($app)
		{
			$appId 		  = $app['config']->get('facebook::app_id');
			$appSecret 	  = $app['config']->get('facebook::app_secret');
			$redirect_url = $app['config']->get('facebook::redirect_url');

			return new Facebook($appId, $appSecret, $redirect_url);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('facebook');
	}

}

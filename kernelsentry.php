<?php
/**
 * @package         Kernel Sentry
 * @version         1.0.0
 *
 * @author          Kernel Data Ltd <jaz@kernel.co.uk>
 * @link            http://www.kernel.co.uk
 * @copyright       Copyright © 2020 Kernel Data Ltd All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Joomla\Registry\Registry;
use Joomla\String\StringHelper;

defined('_JEXEC') or die;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Plugin class for Kernel Sentry
 *
 * @since  1.0.0
 */
class plgSystemKernelsentry extends JPlugin
{
	/**
	 * The global exception handler registered before the plugin was instantiated
	 *
	 * @var    callable
	 * @since  1.0.0
	 */
	private static $previousExceptionHandler;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   1.0.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		Sentry\init(
			['dsn' => $this->params['sentry_dsn']],
			['error_types' => implode(' | ', $this->params['error_types'])]
		);

		JError::setErrorHandling(E_ERROR, 'callback', array('PlgSystemKernelsentry', 'handleError'));
		self::$previousExceptionHandler = set_exception_handler(array('PlgSystemKernelsentry', 'handleException'));
	}

	/**
	 * Method to handle an error condition from JError.
	 *
	 * @param   JException  $error  The JException object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public static function handleError(JException $error)
	{
		self::doErrorHandling($error);
	}

	/**
	 * Method to handle an uncaught exception.
	 *
	 * @param   Exception|Throwable  $exception  The Exception or Throwable object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 * @throws  InvalidArgumentException
	 */
	public static function handleException($exception)
	{
		// If this isn't a Throwable then bail out
		if (!($exception instanceof Throwable) && !($exception instanceof Exception))
		{
			throw new InvalidArgumentException(
				sprintf('The error handler requires an Exception or Throwable object, a "%s" object was given instead.', get_class($exception))
			);
		}

		self::doErrorHandling($exception);
	}

	/**
	 * Processor for all error handlers
	 *
	 * @param   Exception|Throwable  $error  The Exception or Throwable object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	private static function doErrorHandling($error)
	{
		$app = JFactory::getApplication();
		if ($app->isClient('administrator'))
		{
			return;
		}

		Sentry\captureException($error);
	}
}
<?php

namespace Drupal\anonymous_login\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AnonymousLoginSubscriber.
 *
 * @package Drupal\anonymous_login
 */
class AnonymousLoginSubscriber implements EventSubscriberInterface {

  use MessengerTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The instantiated account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The Server API for this build of PHP.
   *
   * @var string
   */
  protected $sapi;

  /**
   * Constructor of a new AnonymousLoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state system.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The instantiated account.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param string $sapi
   *   (Optional) The Server API for this build of PHP.
   *   We need it to prevent skipping during tests.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              StateInterface $state,
                              AccountProxyInterface $current_user,
                              PathMatcherInterface $path_matcher,
                              AliasManagerInterface $alias_manager,
                              ModuleHandlerInterface $module_handler,
                              PathValidatorInterface $path_validator,
                              CurrentPathStack $current_path,
                              $sapi = PHP_SAPI) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->aliasManager = $alias_manager;
    $this->moduleHandler = $module_handler;
    $this->pathValidator = $path_validator;
    $this->currentPath = $current_path;
    $this->sapi = $sapi;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['redirect', 100];
    return $events;
  }

  /**
   * Perform the anonymous user redirection, if needed.
   *
   * This method is called whenever the KernelEvents::REQUEST event is
   * dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The Event to process.
   */
  public function redirect(RequestEvent $event) {
    // Skip if maintenance mode is enabled.
    if ($this->state->get('system.maintenance_mode')) {
      return;
    }

    // Skip if running from the command-line.
    if ($this->sapi === 'cli') {
      return;
    }

    // Skip if no paths are configured for redirecting.
    if (!($paths = $this->paths()) || empty($paths['include'])) {
      return;
    }

    // Skip if the user is not anonymous.
    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    // Get current request.
    $request = $event->getRequest();

    // Determine the current path and alias.
    $current_path = $this
      ->aliasManager
      ->getPathByAlias($this->currentPath->getPath($request));
    $current = [
      'path' => $current_path,
      'alias' => $this->aliasManager->getAliasByPath($current_path),
    ];

    // Ignore PHP file requests.
    if (substr($current['path'], -4) == '.php') {
      return;
    }

    $login_page = '/user/login';

    // Validate path to prevent system error if path doesn't exist
    // or it was deleted for some reason.
    $valid_url = $this->pathValidator
      ->getUrlIfValidWithoutAccessCheck($this->configFactory->get('anonymous_login.settings')->get('login_path'));
    if ($valid_url) {
      // We use this method to get the path with current language prefix
      // if site is multilingual.
      $login_page = Url::fromUserInput('/' . $valid_url->getInternalPath())->toString();
    }

    // Ignore the user login page.
    if ($current['path'] == $login_page) {
      return;
    }

    // Convert the path to the front page token, if needed.
    $front_page = $this->configFactory->get('system.site')->get('page.front');
    $current['path'] = ($current['path'] != $front_page) ? $current['path'] : '<front>';

    // Track if we should redirect.
    $redirect = FALSE;

    // Iterate the current path and alias.
    foreach ($current as &$check) {
      // Remove the leading or trailer slash.
      $check = $this->pathSlashCut($check);

      // Redirect if the path is a match for included paths.
      if ($this->pathMatcher->matchPath($check, implode(PHP_EOL, $paths['include']))) {
        $redirect = TRUE;
      }
      // Do not redirect if the path is a match for excluded paths.
      if (!empty($paths['exclude']) && $this->pathMatcher->matchPath($check, implode(PHP_EOL, $paths['exclude']))) {
        $redirect = FALSE;
        // Matching an excluded path is a hard-stop.
        break;
      }
    }

    // See if we're going to redirect.
    if ($redirect) {
      // See if we have a message to display.
      if ($message = $this->configFactory->get('anonymous_login.settings')->get('message')) {
        $this->messenger()->addStatus($message);
      }

      // Ensure the query is also passed along.
      $query = '';
      if ($request->query->count()) {
        $query = '?' . str_replace('&', '%26', $request->getQueryString());
      }

      // Remove destination parameter from current request
      // to prevent double redirection.
      if ($request->query->has('destination')) {
        $request->query->remove('destination');
      }

      // Redirect to the login, keeping the requested alias as the destination.
      $event->setResponse(new RedirectResponse($login_page . '?destination=' . $current['alias'] . $query));
    }
  }

  /**
   * Fetch the paths.
   *
   * That should be used when determining when to force
   * anonymous users to login.
   *
   * @return array
   *   An array of paths, keyed by "include", paths that should force a
   *   login, and "exclude", paths that should be ignored.
   */
  public function paths() {
    // Initialize the paths array.
    $paths = [
      'include' => [],
      'exclude' => [],
    ];

    // Fetch the stored paths set in the admin settings.
    if ($setting = $this->configFactory->get('anonymous_login.settings')->get('paths')) {
      // Split by each newline.
      $setting = explode(PHP_EOL, $setting);

      // Iterate each path and determine if the path should be included
      // or excluded.
      foreach ($setting as $path) {
        if (substr($path, 0, 1) == '~') {
          $path = substr($path, 1);
          $path = $this->pathSlashCut($path);
          $paths['exclude'][] = $path;
        }
        else {
          $path = $this->pathSlashCut($path);
          $paths['include'][] = $path;
        }
      }
    }

    // Always exclude certain paths.
    $paths['exclude'][] = 'user/reset/*';
    $paths['exclude'][] = 'cron/*';
    $paths['exclude'][] = 'sites/default/files/*';

    // Allow other modules to alter the paths.
    $this->moduleHandler->alter('anonymous_login_paths', $paths);

    return $paths;
  }

  /**
   * Cut leading and trailer slashes, if needed.
   *
   * @param string $path_string
   *   String which contains page path.
   *
   * @return string
   *   String which contains clean page path.
   */
  public function pathSlashCut($path_string) {
    // Remove the leading slash.
    if (substr($path_string, 0, 1) == '/') {
      $path_string = substr($path_string, 1);
    }

    // Remove the trailer slash.
    if (substr($path_string, -1) == '/') {
      $path_string = substr($path_string, 0, strlen($path_string) - 1);
    }

    return $path_string;
  }

}

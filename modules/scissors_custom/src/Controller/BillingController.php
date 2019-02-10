<?php

namespace Drupal\scissors_custom\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Database;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\comment\Entity\Comment;

class BillingController extends ControllerBase
{
  public function viewBilling(AccountInterface $user)
  {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $CI = $this->billingAccess();
    $o = "";
    $element = array(
      '#markup' => $o,
    );
    return $element;
  }

  private function billingAccess($controller = 'login', $function = 'index'){
    ob_start();
    $_SERVER['QUERY_STRING'] = '';
    $routing['controller'] = $controller;
    $routing['function']  = $function;
    define("REQUEST", "external");
    require_once DRUPAL_ROOT.'\billing\public\external.php';
    ob_end_clean();
    $CI =& get_instance();
    return $CI;
  }
}

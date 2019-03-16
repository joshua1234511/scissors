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
    $user = \Drupal::currentUser();
    $user_email = $user->getEmail();
    $connection = \Drupal::database();
    $query = $connection->select('ospos_people', 'p');
    $query->condition('p.email', $user_email);
    $query->fields('p',['person_id']);
    $query->leftJoin('ospos_customers', 'c', 'p.person_id = c.person_id');
    $query->condition('c.person_id', '', '<>');
    $query->fields('c'['person_id']);
    $query->range(0,1);
    $result = $query->execute()->fetchField();
    if(!empty($result)){
      // Sales
      $saleRecords = array();
      $person_id = $result;
      $query = $connection->select('ospos_sales', 's');
      $query->condition('s.customer_id', $person_id);
      $query->condition('s.sale_status', 0);
      $query->fields('s',['sale_time','sale_id']);
      $query->orderBy('s.sale_time', 'DESC');
      $saleResult = $query->execute();
      if(!empty($saleResult)){
        foreach ($saleResult as $sale) {
          $saleRecords[$sale->sale_id]['sales'] = (array)$sale;
          $saleRecords[$sale->sale_id]['sales']['total'] = 0;
          $saleRecords[$sale->sale_id]['sales']['sub_total'] = 0;
          $saleRecords[$sale->sale_id]['sales']['quantity'] = 0;
          $saleRecords[$sale->sale_id]['sales']['payment_type'] = array();
          // Sales Item
          $saleItems = array();
          $query = $connection->select('ospos_sales_items', 'i');
          $query->condition('i.sale_id', $sale->sale_id);
          $query->fields('i',['item_id','quantity_purchased','item_cost_price','discount_percent']);
          $query->leftJoin('ospos_items', 'a', 'i.item_id = a.item_id');
          $query->fields('a',['name']);
          $saleItemsResult = $query->execute();
          if(!empty($saleItemsResult)){
            foreach ($saleItemsResult as $saleItem) {
              $saleItems[$saleItem->item_id] = (array)$saleItem;
              $saleItems[$saleItem->item_id]['total'] = $saleItem->item_cost_price * $saleItem->quantity_purchased;
              $saleItems[$saleItem->item_id]['total'] = $saleItems[$saleItem->item_id]['total'] - (($saleItem->discount_percent / 100) * $saleItems[$saleItem->item_id]['total']);
              $saleItems[$saleItem->item_id]['sub_total'] = $saleItem->item_cost_price * $saleItem->quantity_purchased;
              $saleRecords[$sale->sale_id]['sales']['sub_total'] += $saleItems[$saleItem->item_id]['sub_total'];
              $saleRecords[$sale->sale_id]['sales']['total'] += $saleItems[$saleItem->item_id]['total'];
              $saleRecords[$sale->sale_id]['sales']['quantity'] += $saleItem->quantity_purchased;
            }
            $saleRecords[$sale->sale_id]['items'] = (array)$saleItems;
          }
          // Sales Payment
          $salePayments = array();
          $query = $connection->select('ospos_sales_payments', 'pay');
          $query->condition('pay.sale_id', $sale->sale_id);
          $query->fields('pay',['payment_amount','payment_type']);
          $salePaymentsResult = $query->execute();
          if(!empty($salePaymentsResult)){
            foreach ($salePaymentsResult as $salePayment) {
              $salePayments[] = (array)$salePayment;
              $saleRecords[$sale->sale_id]['sales']['payment_type'][] = $salePayment->payment_type.' '.$salePayment->payment_amount;
            }
            $saleRecords[$sale->sale_id]['payments'] = (array)$salePayments;
          }
          // Sales Reward
          $saleRewards = array();
          $query = $connection->select('ospos_sales_reward_points', 'r');
          $query->condition('r.sale_id', $sale->sale_id);
          $query->fields('r',['earned','used']);
          $saleRewardsResult = $query->execute();
          if(!empty($saleRewardsResult)){
            foreach ($saleRewardsResult as $saleReward) {
              $saleRewards[] = (array)$saleReward;
            }
            $saleRecords[$sale->sale_id]['rewards'] = (array)$saleRewards;
          }
         }
      }
    }
    $header = [
      ['data' => $this->t('Date')],
      ['data' => $this->t('Quantity')],
      ['data' => $this->t('Subtotal')],
      ['data' => $this->t('Total')],
      ['data' => $this->t('Payment Type')],
    ];
    $headerProducts = [
      ['data' => $this->t('Name')],
      ['data' => $this->t('Quantity')],
      ['data' => $this->t('Subtotal')],
      ['data' => $this->t('Total')],
      ['data' => $this->t('Discount')],
    ];
    $headerRewards = [
      ['data' => $this->t('Points Used')],
      ['data' => $this->t('Points Earned')],
    ];
    $limit = 20;
    $saleRecords = $this->pagerArraySplice($saleRecords, $limit);
    if(!empty($saleRecords)){
      foreach ($saleRecords as $key => $value) {
        $build['config_table'][] = [
          '#markup' => '<h3>Trans. ID: '.$value['sales']['sale_id'].'</h3>',
        ];
        $saleDetails = array();
        $saleDetails[$key][] = $value['sales']['sale_time'];
        $saleDetails[$key][] = $value['sales']['quantity'];
        $saleDetails[$key][] = $value['sales']['sub_total'];
        $saleDetails[$key][] = $value['sales']['total'];
        $saleDetails[$key][] = implode(', ', $value['sales']['payment_type']);
        $build['config_table'][] = [
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $saleDetails,
          '#empty' => t('Sorry, there is no results to show'),
        ];
        $rowsProducts = array();
        foreach ($value['items'] as $key => $item) {
          $rowsProducts[$key][] = $item['name'];
          $rowsProducts[$key][] = $item['quantity_purchased'];
          $rowsProducts[$key][] = $item['sub_total'];
          $rowsProducts[$key][] = $item['total'];
          $rowsProducts[$key][] = $item['discount_percent'].'%';
        }
        $build['config_table'][] = [
          '#theme' => 'table',
          '#header' => $headerProducts,
          '#rows' => $rowsProducts,
          '#empty' => t('Sorry, there is no results to show'),
        ];
        $rowsRewards = array();
        foreach ($value['rewards'] as $key => $reward) {
          $rowsRewards[$key][] = $reward['used'];
          $rowsRewards[$key][] = $reward['earned'];
        }
        $build['config_table'][] = [
          '#theme' => 'table',
          '#header' => $headerRewards,
          '#rows' => $rowsRewards,
          '#empty' => t('Sorry, there is no results to show'),
        ];
      }
    }
    // Finally add the pager.
    $build['pager'] = [
      '#type' => 'pager',
    ];
    return $build;
  }
  /**
   * Array splice function.
   */
  public function pagerArraySplice($data, $limit = 10, $element = 0) {
    global $pager_page_array, $pager_total, $pager_total_items;
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    // Convert comma-separated $page to an array, used by other functions.
    $pager_page_array = explode(',', $page);
    // We calculate the total of pages as ceil(items / limit).
    $pager_total_items[$element] = count($data);
    $pager_total[$element] = ceil($pager_total_items[$element] / $limit);
    $pager_page_array[$element] = max(0, min((int) $pager_page_array[$element], ((int) $pager_total[$element]) - 1));
    return array_slice($data, $pager_page_array[$element] * $limit, $limit, TRUE);
  }
}

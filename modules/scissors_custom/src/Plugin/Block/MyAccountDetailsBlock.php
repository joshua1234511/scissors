<?php

namespace Drupal\scissors_custom\Plugin\Block;

use Drupal\Core\Link;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Class MyAccountDetailsBlock.
 *
 * @Block(
 *   id = "my_account_details_block",
 *   admin_label = @Translation("My Account Details Block"),
 * )
 */
class MyAccountDetailsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $o = '';
    $user = \Drupal::currentUser();
    $user_email = $user->getEmail();
    $connection = \Drupal::database();
    $query = $connection->select('ospos_people', 'p');
    $query->condition('p.email', $user_email);
    $query->fields('p');
    $query->leftJoin('ospos_customers', 'c', 'p.person_id = c.person_id');
    $query->condition('c.person_id', '', '<>');
    $query->fields('c');
    $query->range(0,1);
    $result = $query->execute();
    $customerDetails = array();
    if(!empty($result)){
      foreach ($result as $record) {
        $record = (array) $record;
        if(isset($record['first_name']) && !empty($record['first_name'])){
          $customerDetails['first_name'] = $record['first_name'];
        }
        if(isset($record['last_name']) && !empty($record['last_name'])){
          $customerDetails['last_name'] = $record['last_name'];
        }
        if(isset($record['phone_number']) && !empty($record['phone_number'])){
          $customerDetails['phone_number'] = $record['phone_number'];
        }
        if(isset($record['email']) && !empty($record['email'])){
          $customerDetails['email'] = $record['email'];
        }
        if(isset($record['address_1']) && !empty($record['address_1'])){
          $customerDetails['address'][] = $record['address_1'];
        }
        if(isset($record['address_2']) && !empty($record['address_2'])){
          $customerDetails['address'][] = $record['address_2'];
        }
        if(isset($record['city']) && !empty($record['city'])){
          $customerDetails['address'][] = $record['city'];
        }
        if(isset($record['state']) && !empty($record['state'])){
          $customerDetails['address'][] = $record['state'];
        }
        if(isset($record['zip']) && !empty($record['zip'])){
          $customerDetails['address'][] = $record['zip'];
        }
        if(isset($record['country']) && !empty($record['country'])){
          $customerDetails['address'][] = $record['country'];
        }
        if(isset($record['points']) && !empty($record['points'])){
          $customerDetails['reward_points'] = $record['points'];
        }
      }
    }
    if(!empty($customerDetails)){
      $o = '<div class="row"><div class="col-md-12">';
      foreach ($customerDetails as $key => $value) {
        if(is_array($value)){
          $o .= '<p><span>'.ucwords(str_replace('_', ' ', $key)).':</span> ';
          $o .= implode(', ', $value);
          $o .= '</p>';
        }else{
          $o .= '<p><span>'.ucwords(str_replace('_', ' ', $key)).':</span> '.$value.'</p>';
        }
      }
      $o .= '</div></div>';
    }
    return array(
      '#markup' => $o,
    );

  }

}

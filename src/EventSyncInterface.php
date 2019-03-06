<?php
/**
 * Created by PhpStorm.
 * User: wannes
 * Date: 05/03/2019
 * Time: 16:23
 */

namespace Drupal\civicrm_event_sync;


interface EventSyncInterface {

  /**
   * @param $params
   *
   * @return mixed
   */
  public function set($params);

  /**
   * @param $id
   * @param $origin
   *
   * @return mixed
   */
  public function getByOrigin($id, $origin);

}
<?php
  error_reporting(E_ALL ^ E_DEPRECATED);
  include "config.php";


  class TreasureHunt {
    private $treasureHuntObj;
    private $tableName = 'treasurehunt_info';

    public function __construct($reqMethod = null) {
      $this->treasureHuntObj = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_DBNAME, DB_USER, DB_PASS);
      switch ($reqMethod) {
        case 'GET':
          $target = $this->getFormData('target');
          if(empty($target)) {
            $this->getTreasureHuntData();
          } else {
            $this->getTreasureHuntDataByTarget($target);
          }
          break;
        case 'POST':
          $this->updateTreasureHunt();
          break;
        case 'DELETE':
          $target = $this->getFormData('target');
          $this->deleteTreasureHunt($target);
          break;
        default:
          header("HTTP/1.0 405 Method Not Allowed");
          break;
      }

    }

    /**
     * Retrieve data list from DB
     * @return void
     */
    protected function getTreasureHuntData() {
      if(!$this->treasureHuntObj) return null;

      $huntArr = [];
      foreach($this->treasureHuntObj->query("SELECT target, GROUP_CONCAT(object) AS objects FROM treasurehunt_info GROUP BY target") as $row) {
        $huntArr[$row['target']] = explode(',', $row['objects']);
      }

      $this->apiResponse($huntArr);
    }

    /**
     * @param string $target
     * @return void
     */
    protected function getTreasureHuntDataByTarget($target = '') {
      if(!$this->treasureHuntObj || empty($target)) return null;

//      $dbObj->query("*", "target = '{$target}'", 0);
      $query = $this->treasureHuntObj->prepare('SELECT * FROM ' . $this->tableName . ' WHERE target = ?');
      $query->bindParam(1, $target, PDO::PARAM_STR);
      $query->execute();

      $huntArr = [];
      while($res = $query->fetch(PDO::FETCH_ASSOC)) {
        $huntArr[$target][] = $res['object'];
      }

      $this->apiResponse($huntArr);
    }

    protected function updateTreasureHunt() {
      if(!$this->treasureHuntObj) return null;

      $data = json_decode(file_get_contents('php://input'), true);
      $target = htmlentities(trim($data['target']));
      $object = htmlentities(trim($data['object']));

      $query = $this->treasureHuntObj->prepare("INSERT INTO treasurehunt_info (target, object) VALUES(?, ?)");
      $query->bindParam(1, $target, PDO::PARAM_STR);
      $query->bindParam(2, $object, PDO::PARAM_STR);
      $query->execute();

      if($query->rowCount()) {
        $this->jsonResponse(1, "Record submitted.");
      }
      else {
        $this->jsonResponse(0, "There was a problem submitting the treasurehunt data.  Try again later.");
      }
    }

    /**
     * @param string $target
     * @return void
     */
    protected function deleteTreasureHunt($target = '') {
      if (!$this->treasureHuntObj || !$target) return null;

      $query = $this->treasureHuntObj->prepare("DELETE FROM treasurehunt_info WHERE target = ?");
      $query->bindParam(1, $target, PDO::PARAM_STR);
      $query->execute();


      if ($query->rowCount()) {
        $count = $query->rowCount();
        if ($count <= 1) {
          $this->jsonResponse(1, "1 record deleted.");
        } else {
          $this->jsonResponse(1, $count . " records deleted.");
        }
      } else {
        $this->jsonResponse(0, "There was a problem deleting the treasurehunt data.  Try again later.");
      }
    }

      /**
     * This is just an old helper function of mine I threw in to expediate things.
     * @param string $key - The key being retrieved
     * @param bool $cleanse - if true, will strip potentially harmful elements from returned value
     * @return null|string|string[]
     */
    private function getFormData($key = '', $cleanse = true) {
      if (empty($key)) return '';

      $ret = (isset($_POST[$key]) ? $_POST[$key] : '');
      if (empty($ret)) $ret = (isset($_GET[$key]) ? $_GET[$key] : '');
      if (empty($ret)) return $ret;

      if ($cleanse) {
        $search = array('@<script[^>]*?>.*?</script>@si',   // Strip out javascript
          '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
          '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
          '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
        );
        $ret = preg_replace($search, '', $ret);
      } else
        $ret = stripslashes($ret);
      return $ret;
    }

    /**
     * Just a shortcut for returning a JSON response.
     *
     * @param int $status - Status code of transaction: generally 0 for fail, 1 for success
     * @param string $msg - Message to send back to user, interpreted on their side.
     */
    private function jsonResponse($status = 0, $msg = '') {
      header('Cache-Control: no-cache, must-revalidate');
      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
      header('Content-type: application/json');

      echo json_encode(array("status" => (int)$status, "message" => $msg));
    }

    private function apiResponse($arr) {
      header("Access-Control-Allow-Origin: *");
      header("Content-Type: application/json; charset=UTF-8");
      echo json_encode($arr);
    }
  }

  $reqMethod = $_SERVER['REQUEST_METHOD'];
  $treasureHunt = new TreasureHunt($reqMethod);

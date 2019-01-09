<?php
/**
 * Created by PhpStorm.
 * User: ddiminic
 * Date: 2019-01-05
 * Time: 11:44
 */


class Response_Object {
  /**
   * @var array
   */
  private $visited;
  /**
   * @var array
   */
  private $cleaned;
  /**
   * @var
   */
  private $final;
  /**
   * @var
   */
  private $battery;

  /**
   * ResponseObject constructor.
   *
   * @param array  $visited
   * @param array  $cleaned
   * @param int    $finalPositionX
   * @param int    $finalPositionY
   * @param string $finalOrientation
   * @param int    $battery
   */
  public function __construct(array $visited, array $cleaned, int $finalPositionX, int $finalPositionY, string $finalOrientation, int $battery) {
    $this->visited = $this->removeDuplicatesAndSortArray($visited);
    $this->cleaned = $this->removeDuplicatesAndSortArray($cleaned);
    $this->final['X'] = $finalPositionX;
    $this->final['Y'] = $finalPositionY;
    $this->final['facing'] = $finalOrientation;
    $this->battery = $battery;
  }

  /**
   * Removes duplicates from array and sorts it first by X column, and then by Y column
   * @param array $rawArray
   *
   * @return array
   */
  private function removeDuplicatesAndSortArray(array $rawArray) : array {
    array_multisort(
      array_column($rawArray, 'X'), SORT_ASC,
      array_column($rawArray, 'Y'), SORT_ASC,
      $rawArray
    );

    return array_map("unserialize", array_unique(array_map("serialize", $rawArray)));
  }

  /**
   * Returns JSON encoded response
   * @return string
   */
  public function jsonResponse(): string {

    header_remove();
    http_response_code(200);
    header('Content-Type: application/json');
    header('Status: 200');
    $response = [
      'visited' => $this->visited,
      'cleaned' => $this->cleaned,
      'final' => $this->final,
      'battery' => $this->battery
    ];

    return json_encode($response,JSON_PRETTY_PRINT);
  }

  public function jsonError(string $error) {
    header_remove();
    http_response_code(400);
    header('Content-Type: application/json');
    header('Status: 400');
    $response = [
      'error'=>$error
    ];
    return json_encode($response,JSON_PRETTY_PRINT);
  }


}
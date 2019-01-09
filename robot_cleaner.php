<?php
require 'response_object.php';
require 'constants.php';


class Robot_Cleaner {
  /**
   * Map to clean
   *
   * @var array
   */
  private $map;

  /**
   * Set of commands to execute
   *
   * @var array
   */
  private $commands;

  /**
   * Current X position
   *
   * @var int
   */
  private $posX;
  /**
   * Current y position
   *
   * @var int
   */
  private $posY;

  /**
   * Current facing direction
   *
   * @var string
   */
  private $currentFacing;

  /**
   * add to X for direction
   *
   * @var int
   */
  private $directionX;
  /**
   * Add to y for direction
   *
   * @var int
   */
  private $directionY;

  /**
   * Current obstacle avoidance pattern
   *
   * @var int
   */
  private $currentObstacleAvoidancePattern = 0;

  /**
   * Visited fields
   *
   * @var array
   */
  private $visited;
  /**
   * Cleaned fields
   *
   * @var array
   */
  private $cleaned;

  /**
   * Battery low indicator
   *
   * @var bool
   */
  private $batteryLow;
  /**
   * Battery percentage
   *
   * @var int
   */
  private $battery;

  /**
   * Robot_Cleaner constructor.
   *
   * @param array $map
   * @param array $startPosition
   * @param array $commands
   * @param int   $battery
   */
  public function __construct(array $map, array $startPosition, array $commands, int $battery) {
    $this->map = $map;

    $this->posX = $startPosition['X'];
    $this->posY = $startPosition['Y'];
    $this->currentFacing = $startPosition['facing'];

    $this->visited = [];
    $this->cleaned = [];
    $this->commands = $commands;
    $this->battery = $battery;
  }


  /**
   * Run the robot
   *
   * @return false|string
   */
  public function run() {
    $error = '';
    try {
      $this->checkIfFacingIsValid($this->currentFacing);
      foreach ($this->commands as $command) {
        if ($this->batteryLow) {
          break;
        }
        $this->executeCommand($command);
      }
    } catch (Exception $e) {
      $error = $e->getMessage();
    }

    $responseObject = new Response_Object(
      $this->visited, $this->cleaned, $this->posX, $this->posY, $this->currentFacing, $this->battery
    );
    if (!empty($error)) {
      return $responseObject->jsonError($error);
    }

    return $responseObject->jsonResponse();
  }


  /**
   * Executes the passed command
   *
   * @param string $command Command to execute
   *
   * @return void
   * @throws Exception
   */
  private function executeCommand(string $command) {
    switch ($command) {
      case Constants::COMMANDS['TURN_LEFT']:
        $this->changeDirection($command);
        break;
      case Constants::COMMANDS['TURN_RIGHT']:
        $this->changeDirection($command);
        break;
      case Constants::COMMANDS['ADVANCE']:
        $this->moveForward();
        break;
      case Constants::COMMANDS['BACK'];
        $this->moveBackwards();
        break;
      case Constants::COMMANDS['CLEAN']:
        $this->clean();
        break;
      default:
        throw new Exception('Wrong command!');
        break;
    }
  }

  /**
   * Changes direction
   *
   * @param string $command Command to run (has to be command for rotation)
   *
   * @throws Exception
   */
  private function changeDirection(string $command) {
    if ($this->checkBatteryLow(Constants::COMMANDS_COST['TURN'])) {
      return;
    }
    $directions = Constants::FACING;
    // set the current direction
    current($directions);
    while (key($directions) != $this->currentFacing) {
      next($directions);
    }
    // change direction
    switch ($command) {
      case Constants::COMMANDS['TURN_LEFT']:
        next($directions) !== false ? current($directions) : reset($directions);
        break;
      case Constants::COMMANDS['TURN_RIGHT']:
        prev($directions) !== false ? current($directions) : end($directions);
        break;
      default:
        throw new Exception("Wrong command for rotation!");
        break;
    }
    $this->battery -= Constants::COMMANDS_COST['TURN'];
    $this->currentFacing = key($directions);
    $this->directionX = current($directions)['x'];
    $this->directionY = current($directions)['y'];

  }

  /**
   * Move forward
   *
   * @return void
   * @throws Exception
   */
  private function moveForward() {
    if ($this->checkBatteryLow(Constants::COMMANDS_COST['ADVANCE'])) {
      return;
    }
    $this->visited[] = ['X' => $this->posX, 'Y' => $this->posY];
    $this->battery -= Constants::COMMANDS_COST['ADVANCE'];
    $nextPosX = $this->posX + $this->directionX;
    $nextPosY = $this->posY + $this->directionY;
    if ($this->checkIfPositionIsInvalid($nextPosX,$nextPosY)) {
      $this->obstacleAvoidance();
      return;
    }
    $this->currentObstacleAvoidancePattern = 0;
    $this->posX += $this->directionX;
    $this->posY += $this->directionY;
  }

  /**
   * Move backwards
   *
   * @return void
   * @throws Exception
   */
  private function moveBackwards() {
    if ($this->checkBatteryLow(Constants::COMMANDS_COST['BACK'])) {
      return;
    }
    $nextPosX = $this->posX - $this->directionX;
    $nextPosY = $this->posY - $this->directionY;
    if ($this->checkIfPositionIsInvalid($nextPosX,$nextPosY)) {
      return;
    }
    $this->posX -= $this->directionX;
    $this->posY -= $this->directionY;
    $this->battery -= Constants::COMMANDS_COST['BACK'];
  }

  /**
   * Cleans the current position
   *
   * @return void
   */
  private function clean() {
    if ($this->checkBatteryLow(Constants::COMMANDS_COST['CLEAN'])) {
      return;
    }
    $this->battery -= Constants::COMMANDS_COST['CLEAN'];
    $this->cleaned[] = ['X' => $this->posX, 'Y' => $this->posY];
  }

  /**
   * Obstacle avoidance algorithm
   *
   * @return void
   * @throws Exception
   */
  private function obstacleAvoidance() {
    $pattern = isset(Constants::OBSTACLE_AVOIDANCE_PATTERNS[$this->currentObstacleAvoidancePattern]) ?
      Constants::OBSTACLE_AVOIDANCE_PATTERNS[$this->currentObstacleAvoidancePattern] : null;
    if (null == $pattern) {
      return;
    }
    $this->currentObstacleAvoidancePattern++;

    foreach ($pattern as $command) {
      $this->executeCommand($command);
    }

  }

  /**
   * Checks if position is invalid, throws exception if the map field is set but with invalid designation
   *
   * @param int $x X position
   * @param int $y Y position
   *
   * @return bool
   * @throws Exception
   */
  private function checkIfPositionIsInvalid(int $x, int $y) : bool {
    if (isset($this->map[$y][$x]) && !in_array($this->map[$y][$x], Constants::VALID_MAP_DESIGNATIONS)){
      throw new Exception('Map designation invalid!');
    }
    return (!isset($this->map[$y][$x]) || $this->map[$y][$x] == null ||
            $this->map[$y][$x] == 'C');

  }

  /**
   * Checks if the direction is set correctly
   *
   * @param string $facing current facing direction
   *
   * @return void
   * @throws Exception
   */
  private function checkIfFacingIsValid(string $facing) {
      if (!array_key_exists($facing, Constants::FACING))
      {
        throw new Exception('Invalid facing direction!');
      }
  }

  /**
   * Check if battery is running low
   *
   * @param int $batteryCost
   *
   * @return bool
   */
  private function checkBatteryLow(int $batteryCost) {
    if (($this->battery - $batteryCost) < 0) {
      $this->batteryLow = true;

      return true;
    }

    return false;
  }


}
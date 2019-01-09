<?php


class Constants {
  const COMMANDS = [
    'TURN_LEFT' => 'TL',
    'TURN_RIGHT' => 'TR',
    'ADVANCE' => 'A',
    'BACK' => 'B',
    'CLEAN' => 'C'
  ];

  const COMMANDS_COST = [
    'TURN' => 1,
    'ADVANCE' => 2,
    'BACK' => 3,
    'CLEAN' => 5
  ];

  const FACING = [
    'N' => ['x' => 0, 'y' => -1],
    'W' => ['x' => -1, 'y' => 0],
    'S' => ['x' => 0, 'y' => 1],
    'E' => ['x' => 1, 'y' => 0]
  ];

  const OBSTACLE_AVOIDANCE_PATTERNS = [
    ['TR', 'A'],
    ['TL', 'B', 'TR', 'A'],
    ['TL', 'TL', 'A'],
    ['TR', 'B', 'TR', 'A'],
    ['TL', 'TL', 'A']
  ];

  const VALID_MAP_DESIGNATIONS = ['S', 'C', null];
}
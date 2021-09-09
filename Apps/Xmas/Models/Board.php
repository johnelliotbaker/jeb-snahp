<?php
namespace jeb\snahp\Apps\Xmas\Models;

require_once '/var/www/forum/ext/jeb/snahp/core/Rest/Model.php';
require_once '/var/www/forum/ext/jeb/snahp/Apps/Xmas/BingoBoard.php';

use jeb\snahp\core\Rest\Model;
use jeb\snahp\core\Rest\Fields\IntegerField;
use jeb\snahp\core\Rest\Fields\StringField;
use jeb\snahp\core\Rest\Fields\JsonField;

use \R as R;

use jeb\snahp\Apps\Xmas\BingoBoard;

class Board extends Model
{
    // Board definitions
    const POOL_SIZE = 13;
    const ROWS = 3;
    const COLUMNS = 3;
    const NUMBER_OF_TILES = 9;

    const TABLE_NAME = 'phpbb_snahp_xmas_board';

    protected $fields;
    protected $requiredFields = ['user', 'tiles', 'created'];

    public function __construct()
    {
        parent::__construct();
        $this->fields = [
            'user' => new IntegerField(),
            'tiles' => new JsonField(['default' => json_encode([])]),
            'created' => new IntegerField(['default' => time()]),
        ];
    }

    public function performPreCreate($data)
    {
        // Generate random tiles on create
        $bingoBoard = new BingoBoard($this::ROWS, $this::COLUMNS, $this::POOL_SIZE);
        $bingoBoard->makeRandom();
        $data['tiles'] = json_encode($bingoBoard->tiles);
        return $data;
    }
}

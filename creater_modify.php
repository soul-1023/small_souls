<?
class S
{
    private $db, $table;
    public $current;

    // задает подкючение к бд
    public function __construct($db, $table)
    {
        $this->db    = $db;
        $this->table = $table;
    }

    // добавляет разделить в строку
    private function add_separator($v, $sep, $type = null)
    {
        if (is_numeric($v))  return "$v" . $sep;
        else if ($type === 'row') return "`$v`" . $sep;
        else if ($type === 'set' && $v !== null) return $v . $sep;
        else if (is_string($v)) return "'$v'" . $sep;
        else if ($v === null) return 'null' . $sep;
        else if ($v === false) return 'false' . $sep;
        else if ($v === true) return 'true' . $sep;
    }

    // перебирает массив и вставляет значения в строку 
    private function processing($str = '', $type, $arr)
    {
        foreach ($arr as $val) {
            $end = array_pop($val);

            // если массив двумерный
            foreach ($val as $v) {
                $str .= $this->add_separator($v, ', ', $type);
            }

            $str .= $this
                ->add_separator($end, !next($arr) ? '' : '), (', $type);
        }

        return $str;
    }



    // генерирует insert запрос
    public function insert($vals, $row = null)
    {
        $vals = [explode(', ', $vals)];
        $dl   = '';

        if ($row === null) $rl = '';
        else if (is_string($row)) $rl = "`$row`";
        else $rl = $this->processing($rl, 'row', $row);

        $dl = $this->processing($dl, 'val', $vals);

        $this->current = "INSERT INTO `$this->table`"
            . ($row == null ? "" : "($rl)")
            . " VALUES ($dl)";

        return $this;
    }

    // генерирует select  запрос
    public function select($cond = null, $row = '*')
    {
        if (is_string($row)) $rl = $row;
        else $rl = $this->processing($rl, 'row', $row);

        $this->current = "SELECT $rl FROM `$this->table`"
            . ($cond == null ? '' : " WHERE $cond");

        return $this;
    }

    // генерирует update запрос
    public function update($rows, $vals, $cond = null)
    {
        $set = '';

        if (is_string($rows)) {
            $set = $rows . ' = ' . "'$vals'";
        } else {
            $arr_row = [];
            $arr_val = [];
            $end     = array_pop($vals);

            for ($i = 0; $i < count($rows); $i++) {
                if ($i != count($rows) - 1)
                    $arr_val[] = $this->add_separator($vals[$i], ', ', 'val');
                else
                    $arr_val[] = $this->add_separator($end, '', 'val');

                $arr_row[] = $this->add_separator($rows[$i], ' = ', 'set');
                $set     .= $arr_row[$i] . $arr_val[$i];
            }
        }

        $this->current = "UPDATE `$this->table` SET $set"
            . ($cond == null ? "" : " WHERE $cond");

        return $this;
    }

    // отправляет запрос в базу данных, или возвращает ошибку соединения / данные
    public function send()
    {
        $mysqli = new mysqli('localhost', 'root', '', $this->db);

        if ($mysqli->connect_errno) {
            echo "Соединение не удалось: " . $mysqli->connect_error;
        }

        if (!$mysqli->query($this->current)) {
            return "Код ошибки: $mysqli->errno";
        }

        $result = $mysqli->query($this->current);
        $data   = [];

        for ($row = []; $row = $result->fetch_assoc(); $data[] = $row);

        return $data;
    }
}

$special   = new S('protocol', 'specialty');
$group     = new S('protocol', 'groups');
$student   = new S('protocol', 'students');
$spectator = new S('protocol', 'spectators');

$special_data = $special->select()->send();
$group_data   = $group->select()->send();
$stud_data    = $student->select()->send();
$spect_data   = $spectator->select()->send();
?>


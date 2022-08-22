<?php
require "DataBase.php";


function tt(mixed $i)
{
    echo "<pre>";
    print_r($i);
    echo "</pre>";
}

class xmlTestTask
{
    protected $xml, $data;
    protected $db;
    private array $attrXML = [], $attributes = [];


    public function parseXml($string): void
    {
        $this->db = new DataBase();
        $this->xml = simplexml_load_file($string, null, LIBXML_NOCDATA);
        $json = json_encode($this->xml);
        $this->data = json_decode($json, TRUE);
        $this->getAttribute();
        $this->getEquipment();
        $this->db->syncDBData($this->data, $this->attrXML, $this->attributes);
        
    }

    private function getAttribute(): void
    {
        foreach ($this->data['vehicle'] as $item) {
            foreach ($item as $key => $value) {
                if (empty($this->attrXML)) $this->attrXML[] = $key;
                else {
                    $temp = false;
                    foreach ($this->attrXML as $k) {
                        if ($k == $key) {
                            $temp = true;
                            break;
                        }
                    }
                    if (!$temp) $this->attrXML[] = $key;
                }
            }
        }
        
    }

    public function getData($arg)
    {
        $attr = $this->attrXML[$arg];
        foreach ($this->data['vehicle'] as $item) {
            foreach ($item as $key => $value) {
                if ($key == $attr) {
                    if (!empty($value)) {
                        switch ($attr) {
                            case "description": {
                                    echo $item['id'] . " - " . $value . "<br>";
                                    break;
                                }
                        }
                    }
                    break;
                }
            }
        }
    }

    public function getEquipment()
    {
        foreach ($this->data['vehicle'] as $item) {
            foreach ($item as $key => $value) {
                if ($key == $this->attrXML[67]) {
                    if (!empty($value["group"])) {
                        if (empty($value["group"]["@attributes"])) {
                            for ($i = 0; $i < count($value["group"]); $i++) {
                                if (empty($this->attributes)) $this->attributes[] = $value["group"][$i]["@attributes"];
                                else {
                                    $temp = false;
                                    foreach ($this->attributes as $k) {
                                        if ($k['id'] == $value["group"][$i]["@attributes"]['id']) {
                                            $temp = true;
                                            break;
                                        }
                                    }
                                    if (!$temp) $this->attributes[] = $value["group"][$i]["@attributes"];
                                }
                            }
                        } else {
                            if (empty($this->attributes)) $this->attributes[] = $value["group"]["@attributes"];
                            else {
                                $temp = false;
                                foreach ($this->attributes as $k) {
                                    if ($k['id'] == $value["group"]["@attributes"]['id']) {
                                        $temp = true;
                                        break;
                                    }
                                }
                                if (!$temp) $this->attributes[] = $value["group"]["@attributes"];
                            }
                        }
                    }
                }
            }
        }

        $arr = [];
        for ($numAttr = 0; $numAttr < count($this->attributes); $numAttr++) {
            foreach ($this->data['vehicle'] as $item) {
                foreach ($item as $key => $value) {
                    if ($key == $this->attrXML[67]) {
                        if (!empty($value["group"])) {
                            if (empty($value["group"]["@attributes"])) {
                                for ($i = 0; $i < count($value["group"]); $i++) {
                                    if ($value["group"][$i]["@attributes"]["id"] == $this->attributes[$numAttr]['id']) {
                                        $temp = false;
                                        foreach ((array)$value["group"][$i]["element"] as $id => $name) {
                                            if (empty($arr)) $arr[] = $name;
                                            else {
                                                $temp = false;
                                                for ($j = 0; $j < count($arr); $j++) {
                                                    if ($arr[$j] == $name) {
                                                        $temp = true;
                                                        break;
                                                    }
                                                }
                                                if (!$temp) $arr[] = $name;
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($value["group"]["@attributes"]["id"] == $this->attributes[$numAttr]['id']) {
                                    $temp = false;
                                    foreach ((array)$value["group"]["element"] as $id => $name) {
                                        if (empty($arr)) $arr[] = $name;
                                        else {
                                            $temp = false;
                                            for ($j = 0; $j < count($arr); $j++) {
                                                if ($arr[$j] == $name) {
                                                    $temp = true;
                                                    break;
                                                }
                                            }
                                            if (!$temp) $arr[] = $name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $this->attributes[$numAttr]["elements"] = $arr;
            $arr = [];
        }
    }

    public function print(): void
    {
        echo "<pre>";
        print_r($this->data);
        echo "</pre>";
    }

    public function printAttr(): void
    {
        echo "<pre>";
        print_r($this->attrXML);
        echo "</pre>";
    }
}

$start = microtime(true);

switch ($argv[1]) {
    case 'parse': {
            $test = new xmlTestTask();
            if (empty($argv[2]))
                $test->parseXml("data.xml");
            else {
                $test->parseXml($argv[2]);
            }
            break;
        }

    default:
        // Если параметр нам не подходит, или его нет, говорим об ошибке
        echo "Ошибка параметра\n";
        break;
}


echo 'Время выполнения скрипта: ' . round(microtime(true) - $start, 8) . ' сек.';

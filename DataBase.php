<?php

class DataBase
{
    protected $configDB = [
        "servername" => "192.168.50.136",
        "username" => "admin",
        "password" => "",
        "databasename" => "testtask"
    ];
    public $connect;

    private function dbConnect()
    {
        $this->connect = mysqli_connect($this->configDB["servername"], $this->configDB["username"], $this->configDB["password"], $this->configDB["databasename"]);
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
        }
        return $this->connect;
    }

    private function prepareData($data)
    {
        return mysqli_real_escape_string($this->connect, stripslashes(htmlspecialchars($data)));
    }

    private function mysql_table_seek($tablename, $dbname)
    {
        $table_list = mysqli_query($this->connect, "SHOW TABLES FROM `" . $dbname . "`");
        while ($row = mysqli_fetch_row($table_list)) {
            if ($tablename == $row[0]) {
                return true;
            }
        }
        return false;
    }

    function syncDBElementsEquipments($data)
    {
        if (!$this->mysql_table_seek("elementsequipments", $this->configDB["databasename"])) {
            $sql = "CREATE TABLE elementsequipments (
                id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                name TEXT,
                idEquipments INT NOT NULL)";
            if (!mysqli_query($this->connect, $sql)) {
                echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connect);
            }

            for ($i = 0; $i < count($data); $i++) {
                for ($j = 0; $j < count($data[$i]["elements"]); $j++) {
                    $idEquipments = $this->prepareData($data[$i]["id"]);
                    $name = $this->prepareData($data[$i]["elements"][$j]);
                    $sql = "INSERT elementsequipments (name, idEquipments) VALUES ('" . $name . "','" . $idEquipments . "')";
                    mysqli_query($this->connect, $sql);
                }
            }
        } else {
            $sql = "DROP TABLE elementsequipments;";
            mysqli_query($this->connect, $sql);
            $this->syncDBElementsEquipments($data);
        }
    }

    function syncDBEquipment($data)
    {
        if (!$this->mysql_table_seek("equipments", $this->configDB["databasename"])) {
            $sql = "CREATE TABLE equipments (
                    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    name TEXT)";
            if (!mysqli_query($this->connect, $sql)) {
                echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connect);
            }

            for ($i = 0; $i < count($data); $i++) {
                $id = $this->prepareData($data[$i]["id"]);
                $name = $this->prepareData($data[$i]["name"]);
                $sql = "INSERT equipments (id, name) VALUES ('" . $id . "','" . $name . "')";
                mysqli_query($this->connect, $sql);
            }

            $this->syncDBElementsEquipments($data);
        } else {
            $sql = "DROP TABLE equipments;";
            mysqli_query($this->connect, $sql);
            $this->syncDBEquipment($data);
        }
    }

    function syncDBElementsEquipmentsVehicle($data, $elementsequipments)
    {
        if (!$this->mysql_table_seek("elementsequipmentsvehicle", $this->configDB["databasename"])) {
            $sql = "CREATE TABLE elementsequipmentsvehicle (
                    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                    idvehicle INT,
                    idequipment INT)";
            if (!mysqli_query($this->connect, $sql)) {
                echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connect);
            }


            for ($i = 0; $i < count($data['vehicle']); $i++) {
                foreach ($data['vehicle'][$i] as $key => $value) {
                    if ($key == "equipment") {
                        if (empty($value["group"]["element"])) {
                            for ($j = 0; $j < count($value["group"]); $j++) {
                                foreach ((array)$value["group"][$j]["element"] as $id => $name) {
                                    for ($k = 0; $k < count($elementsequipments); $k++) {
                                        if ($name == $elementsequipments[$k]["name"]) {
                                            $sql = "INSERT elementsequipmentsvehicle (idvehicle, idequipment) VALUES ('" . $data['vehicle'][$i]["id"] . "','" . $elementsequipments[$k]["id"] . "')";
                                            mysqli_query($this->connect, $sql);
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ((array)$value["group"]["element"] as $id => $name) {
                                for ($k = 0; $k < count($elementsequipments); $k++) {
                                    if ($name == $elementsequipments[$k]["name"]) {
                                        $sql = "INSERT elementsequipmentsvehicle (idvehicle, idequipment) VALUES ('" . $data['vehicle'][$i]["id"] . "','" . $elementsequipments[$k]["id"] . "')";
                                        mysqli_query($this->connect, $sql);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            $sql = "DROP TABLE elementsequipmentsvehicle;";
            mysqli_query($this->connect, $sql);
            $this->syncDBElementsEquipmentsVehicle($data, $elementsequipments);
        }
    }

    function add($data, $new_data, $elementsequipments)
    {
        $numberNewEntries = 0;
        $numberNewEntries2 = 0;
        foreach ($new_data as $nKey => $nVal) {
            $availability = false;
            foreach ($data as $key => $val) {
                if ($nVal["id"] == $val["id"]) {
                    $availability = true;
                    break;
                }
            }
            if (!$availability) {
                $numberNewEntries++;
                $sql = "INSERT vehicle (";
                foreach ($nVal as $argument => $value) {
                    if ($argument != "equipment") {
                        if (!empty($value)) {
                            $sql = $sql . $argument . ", ";
                        }
                    } else if ($argument == "equipment") {
                        if (empty($value["group"]["element"])) {
                            for ($j = 0; $j < count($value["group"]); $j++) {
                                foreach ((array)$value["group"][$j]["element"] as $id => $name) {
                                    for ($k = 0; $k < count($elementsequipments); $k++) {
                                        if ($name == $elementsequipments[$k]["name"]) {
                                            $sqlElement = "INSERT elementsequipmentsvehicle (idvehicle, idequipment) VALUES ('" . $nVal["id"] . "','" . $elementsequipments[$k]["id"] . "')";
                                            $numberNewEntries2++;
                                            mysqli_query($this->connect, $sqlElement);
                                        }
                                    }
                                }
                            }
                        } else {
                            foreach ((array)$value["group"]["element"] as $id => $name) {
                                for ($k = 0; $k < count($elementsequipments); $k++) {
                                    if ($name == $elementsequipments[$k]["name"]) {
                                        $sqlElement = "INSERT elementsequipmentsvehicle (idvehicle, idequipment) VALUES ('" . $nVal["id"] . "','" . $elementsequipments[$k]["id"] . "')";
                                        $numberNewEntries2++;
                                        mysqli_query($this->connect, $sqlElement);
                                    }
                                }
                            }
                        }
                    }
                }
                $sql = substr_replace($sql, ')', strrpos($sql, ','));
                $sql = $sql . " VALUES (";

                foreach ($nVal as $argument => $value) {
                    if ($argument != "equipment" && $argument != "promoFeatures")
                        if (!empty($value))
                            $sql = $sql . "'" . $value . "', ";
                    if ($argument == "promoFeatures")
                        if (!empty($value))
                            $sql = $sql . "'" . $value["promoFeature"] . "', ";
                }
                $sql = substr_replace($sql, ')', strrpos($sql, ','));
                mysqli_query($this->connect, $sql);
            }
        }

        echo "Количество новых записей в БД:";

        tt("vehicle - " . $numberNewEntries);
        tt("elementsequipmentsvehicle - " . $numberNewEntries2);
    }

    function delete($data, $new_data)
    {
        $numberDeleteEntries = 0;
        $numberDeleteEntries2 = 0;
        foreach ($data as $key => $val) {
            $availability = false;
            foreach ($new_data as $nKey => $nVal) {
                if ($val["id"] == $nVal["id"]) {
                    $availability = true;
                    break;
                }
            }
            if (!$availability) {
                $numberDeleteEntries++;
                $sql = "DELETE FROM `vehicle` WHERE id=" . $val['id'];
                mysqli_query($this->connect, $sql);

                $sql = "SELECT * FROM `elementsequipmentsvehicle` WHERE idvehicle=" . $val['id'];
                $result = mysqli_query($this->connect, $sql);
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $delete_elements[] = $row;
                    }
                }
                if (!empty($delete_elements)) {
                    for ($i = 0; $i < count($delete_elements); $i++) {
                        $numberDeleteEntries2++;
                        $sql = "DELETE FROM `elementsequipmentsvehicle` WHERE id=" . $delete_elements[$i]['id'];
                        mysqli_query($this->connect, $sql);
                    }
                }
            }
        }

        echo "Количество удалёных записей в БД:";

        tt("vehicle - " . $numberDeleteEntries);
        tt("elementsequipmentsvehicle - " . $numberDeleteEntries2);
    }

    function syncDBData($data, $arg, $attributes)
    {
        $this->dbConnect();
        $this->syncDBEquipment($attributes);

        $sql = "SELECT * FROM `elementsequipments`";
        $result = mysqli_query($this->connect, $sql);
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $elementsequipments[] = $row;
            }
        }

        if (!$this->mysql_table_seek("vehicle", $this->configDB["databasename"])) {
            $sql = "CREATE TABLE vehicle (
                    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, ";
            for ($i = 1; $i < count($arg); $i++) {
                if ($i + 1 != count($arg)) {
                    if ($arg[$i] != "equipment")
                        $sql = $sql . $arg[$i] . " TEXT, ";
                } else {
                    $sql = $sql . $arg[$i] . " TEXT)";
                }
            }

            if (!mysqli_query($this->connect, $sql)) {
                echo "ERROR: Could not able to execute $sql. " . mysqli_error($this->connect);
            }

            for ($i = 0; $i < count($data['vehicle']); $i++) {
                $sql = "INSERT vehicle (";
                foreach ($data['vehicle'][$i] as $key => $value) {
                    if ($key != "equipment")
                        if (!empty($data['vehicle'][$i][$key])) {
                            $sql = $sql . $key . ", ";
                        }
                }
                $sql = substr_replace($sql, ')', strrpos($sql, ','));

                $sql = $sql . " VALUES (";

                foreach ($data['vehicle'][$i] as $key => $value) {
                    if ($key != "equipment" && $key != "promoFeatures")
                        if (!empty($data['vehicle'][$i][$key]))
                            $sql = $sql . "'" . $value . "', ";
                    if ($key == "promoFeatures")
                        if (!empty($data['vehicle'][$i][$key]))
                            $sql = $sql . "'" . $value["promoFeature"] . "', ";
                }
                $sql = substr_replace($sql, ')', strrpos($sql, ','));

                mysqli_query($this->connect, $sql);
            }

            $this->syncDBElementsEquipmentsVehicle($data, $elementsequipments);
        } else {
            $sql = "SELECT id FROM vehicle";
            $data_vehicle = [];
            $result = mysqli_query($this->connect, $sql);
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $data_vehicle[] = $row;
                }
            }

            $this->add($data_vehicle, $data["vehicle"], $elementsequipments);
            $this->delete($data_vehicle, $data["vehicle"]);
        }
    }
}

<?php

class Form {
    private $db, $memCache;

    public function __construct() {
        $this->db = new Database();

        $this->memCache = NULL;
        if(USE_MEMCACHED) {
            $this->memCache = new Memcache();
            $this->memCache->connect(MEMCACHED_HOST, MEMCACHED_PORT);
        }
    }

    public function generateReactionSelectForm($scriptName,$buttonText) {
        $reactions = $this->db->getAllReactions();
        echo "<form class=\"form-horizontal\" action=\"{$scriptName}\" method=\"post\">";
        echo "<div class=\"form-group form-group-sm\">";
        echo "<input type=hidden name=\"configure\" value=\"1\">";
        echo "<label class=\"control-label col-xs-4\" for=\"re\">Reaction Type</label>";
        echo "<div class=\"col-xs-8\"><select class=\"form-control\" id=\"re\" name=\"re\">"; // list box select command
        foreach ($reactions as $reaction) {
            $reactionID = $reaction['reactionID'];
            if ($reaction['reactionType'] == 3) {
                $reactionName = $reaction['itemName'] . " Alchemy";
            } else {
                $reactionName = $reaction['itemName'];
            }
            echo "<option value=\"{$reactionID}\">{$reactionName}</option>";
        }
        echo "</select></div>";
        echo "<button class=\"btn btn-xs btn-primary center\" type=\"submit\">{$buttonText}</button></div></form></div>";
    }

    public function generateResetButton($scriptName) {
        echo "<form class=\"form-inline\" style=\"display:inline\" method=\"post\" action=\"{$scriptName}\">";
        echo "<input type=\"hidden\" name=\"reset\" value=\"1\">";
        echo "<button type=\"submit\" class=\"btn btn-xs btn-danger\">Reset</button>";
        echo "</form>";
    }

    public function generatePermalink($options,$reactionID = null,$chain = null) {
        $key = array_search("0",$options);
        while($key !== false) {
            unset($options[$key]);
            $key = array_search("0",$options);
        }
        if ($chain == 1) {
            $options['c'] = $chain;
        }
        if (isset($reactionID)) {
            $options['re'] = $reactionID;
        }
        $argString = http_build_query($options);
        $argCode = base64_encode($argString);
        $permalink = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?p=".$argCode;
        echo $permalink;
    }

    public function generateOptionsForm($scriptName,$buttonText,$options,$reactionID = null) {
        echo "<form class=\"form\" action=\"{$scriptName}\" method=\"post\">";
        echo "<div class=\"form-group form-group-sm\">";
        echo "<input type=\"hidden\" name=\"configured\" value=\"1\">";
        if (isset($reactionID)) {
            $reaction = new Reaction($reactionID);
            echo "<input type=\"hidden\" name=\"re\" value=\"{$reactionID}\">";
        }
        foreach ($options as $optionName => $currentValue) {
            if ($optionName != "c" || (isset($reactionID) && $reaction->getReactionType() == 2)) {
                $this->generateOptionField($optionName,$currentValue);
                echo "<br>";
            }
        }
        echo "<div class=\"center\"><button class=\"btn btn-xs btn-success\" type=\"submit\">{$buttonText}</button></div></div></form>";
    }

    private function generateOptionField($field,$currentValue) {
        switch ($field) {
            case "rn":
                echo "<label class=\"col-xs-8 control-label\" for=\"rn\">Report Range</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"rn\" id=\"rn\">";
                switch ($currentValue) {
                    case "m":
                        echo "<option value=\"d\">Last Day</option>";
                        echo "<option selected value=\"m\">Last Month</option>";
                        echo "<option value=\"y\">Last Year</option>";
                        break;
                    case "d":
                        echo "<option selected value=\"d\">Last Day</option>";
                        echo "<option value=\"m\">Last Month</option>";
                        echo "<option value=\"y\">Last Year</option>";
                        break;
                    case "y":
                        echo "<option value=\"d\">Last Day</option>";
                        echo "<option value=\"m\">Last Month</option>";
                        echo "<option selected value=\"y\">Last Year</option>";
                        break;
                }
                echo "</select></div>";
                break;
            case "r":
                echo "<label class=\"col-xs-8 control-label\" for=\"r\">Tower Race</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"r\" id=\"r\">";
                switch ($currentValue) {
                    case 2:
                        echo "<option selected value=2>Caldari</option>";
                        echo "<option value=3>Gallente</option>";
                        break;
                    case 3:
                        echo "<option value=2>Caldari</option>";
                        echo "<option selected value=3>Gallente</option>";
                        break;
                }
                echo "</select></div>";
                break;
            case "c":
                if ($currentValue):
                    echo "<label class=\"col-xs-8 control-label\" for=\"c\">Chain</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"c\" id=\"c\" value=\"1\" checked></div>";
                else:
                    echo "<label class=\"col-xs-8 control-label\" for=\"c\">Chain</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"c\" id=\"c\" value=\"1\"></div>";
                endif;
                break;
            case "s":
                if ($currentValue):
                    echo "<label class=\"col-xs-8 control-label\" for=\"s\">Sovereignty Bonus</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"s\" id=\"s\" value=\"1\" checked></div>";
                else:
                    echo "<label class=\"col-xs-8 control-label\" for=\"s\">Sovereignty Bonus</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"s\" id=\"s\" value=\"1\"></div>";
                endif;
                break;
            case "g":
                if ($currentValue):
                    echo "<label class=\"col-xs-8 control-label\" for=\"g\">GSF Alt Corp Moon Tax</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"g\" id=\"g\" value=\"1\" checked></div>";
                else:
                    echo "<label class=\"col-xs-8 control-label\" for=\"g\">GSF Alt Corp Moon Tax</label><div class=\"col-xs-4\"><input class=\"form-control\" type=checkbox name=\"g\" id=\"g\" value=\"1\"></div>";
                endif;
                break;
            case "sy":
                $systems = $this->db->getAllSystems();
                echo "<label class=\"col-xs-8 control-label\" for=\"sy\">Market System</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"sy\" id=\"sy\">";
                foreach ($systems as $thisSystemID => $thisSystemName) {
                    if ($currentValue == $thisSystemID):
                        echo "<option selected value=\"{$thisSystemID}\">{$thisSystemName}</option>";
                    else:
                        echo "<option value=\"{$thisSystemID}\">{$thisSystemName}</option>";
                    endif;
                }
                echo "</select></div>";
                break;
            case "b":
                echo "<label class=\"col-xs-8 control-label\" for=\"b\">Broker's Fee (%)</label><div class=\"col-xs-4\"><input class=\"form-control\" type=text name=\"b\" id=\"b\" value=\"{$currentValue}\"></div>";
                break;
            case "st":
                echo "<label class=\"col-xs-8 control-label\" for=\"st\">Sales Tax (%)</label><div class=\"col-xs-4\"><input class=\"form-control\" type=text name=\"st\" id=\"st\" value=\"{$currentValue}\"></div>";
                break;
            case "i":
                echo "<label class=\"col-xs-8 control-label\" for=\"i\">Input Price</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"i\" id=\"i\">";
                if ($currentValue == "b"):
                    echo "<option value=\"s\">Min Sell</option>";
                    echo "<option selected value=\"b\">Max Buy</option>";
                else:
                    echo "<option selected value=\"s\">Min Sell</option>";
                    echo "<option value=\"b\">Max Buy</option>";
                endif;
                echo "</select></div>";
                break;
            case "f":
                echo "<label class=\"col-xs-8 control-label\" for=\"f\">Fuel Price</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"f\" id=\"f\">";
                if ($currentValue == "b"):
                    echo "<option value=\"s\">Min Sell</option>";
                    echo "<option selected value=\"b\">Max Buy</option>";
                else:
                    echo "<option selected value=\"s\">Min Sell</option>";
                    echo "<option value=\"b\">Max Buy</option>";
                endif;
                echo "</select></div>";
                break;
            case "o":
                echo "<label class=\"col-xs-8 control-label\" for=\"o\">Output Price</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"o\" id=\"o\">";
                if ($currentValue == "s"):
                    echo "<option selected value=\"s\">Min Sell</option>";
                    echo "<option value=\"b\">Max Buy</option>";
                else:
                    echo "<option value=\"s\">Min Sell</option>";
                    echo "<option selected value=\"b\">Max Buy</option>";
                endif;
                echo "</select></div>";
                break;
            case "t":
                echo "<label class=\"control-label col-xs-8\" for=\"timeframe\">Net Income Timeframe</label><div class=\"col-xs-4\"><select class=\"form-control\" name=\"t\" id=\"t\">";
                switch ($currentValue) {
                    case "m":
                        echo "<option selected value=\"m\">Monthly</option>";
                        echo "<option value=\"w\">Weekly</option>";
                        echo "<option value=\"d\">Daily</option>";
                        break;
                    case "w":
                        echo "<option value=\"m\">Monthly</option>";
                        echo "<option selected value=\"w\">Weekly</option>";
                        echo "<option value=\"d\">Daily</option>";
                        break;
                    case "d":
                        echo "<option value=\"m\">Monthly</option>";
                        echo "<option value=\"w\">Weekly</option>";
                        echo "<option selected value=\"d\">Daily</option>";
                        break;
                }
                echo "</select></div>";
                break;
        }
    }

} 
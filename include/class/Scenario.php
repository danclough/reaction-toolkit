<?php
class Scenario {
    private $calc, $reaction, $map, $fmt, $race, $sov, $systemName, $systemID, $datetime, $timeframe, $chain, $gsf, $inputPrice, $fuelPrice, $outputPrice, $brokerFee, $salesTax;

    public function __construct($reactionID,$chain,$datetime)
    {
        $this->db = new Database();
        $this->fmt = new Formatter();
        $this->reaction = new Reaction($reactionID);
        $this->race = $_SESSION['params']['r'];
        $this->sov = $_SESSION['params']['s'];
        $this->systemID = $_SESSION['params']['sy'];
        $this->systemName = $this->db->getSystemName($this->systemID);
        $this->datetime = $datetime;
        $this->timeframe = $_SESSION['params']['t'];
        $this->chain = (bool) $chain;
        $this->gsf = $_SESSION['params']['g'];
        $this->inputPrice = $_SESSION['params']['i'];
        $this->fuelPrice = $_SESSION['params']['f'];
        $this->outputPrice = $_SESSION['params']['o'];
        $this->brokerFee = $_SESSION['params']['b']/100;
        $this->salesTax = $_SESSION['params']['st']/100;
        $this->calc = new Calculator($reactionID,$this->chain,$this->datetime);
    }

    public function generateInputTable()
    {
        $inputs = $this->calc->getInputs();
        if ($this->inputPrice == "b") {
            $priceBoundary = "Highest";
            $priceType = "Buy";
        } else {
            $priceBoundary = "Lowest";
            $priceType = "Sell";
        }
        $numCycles = $this->calc->getNumCycles();
        $totalQuantity = 0;
        $totalVolume = 0;
        $totalCost = 0;
        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Material</th><th>Quantity</th><th>Volume</th><th>Cost</th></tr></thead><tbody>";
        if ($this->chain == true) {
            foreach ($inputs as $inputSet) {
                foreach ($inputSet as $input) {
                    $typeID = $input['typeID'];
                    $type = new Type($typeID);
                    $price = $type->getPrice($this->systemID,$this->datetime,$this->inputPrice);
                    $inputQuantity = $input['inputQty']*$numCycles;
                    $itemVolume = $type->getVolume();
                    $inputVolume = $itemVolume*$inputQuantity;
                    $inputCost = $price*$inputQuantity;
                    $totalQuantity += $inputQuantity;
                    $totalVolume += $inputVolume;
                    $totalCost += $inputCost;
                    $itemName = $type->getName();
                    $itemDesc = htmlspecialchars($type->getDescription());
                    $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
                    $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}<br><br>Unit Volume: ".$this->fmt->formatAsM3($itemVolume)."<br>{$priceBoundary} {$this->systemName} {$priceType} Price: ".$this->fmt->formatAsISK($price);
                    echo "<tr><td><a data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" title=\"".$itemName."\" data-content=\"".htmlspecialchars($mouseoverText)."\">{$itemName}</a></td>";
                    echo "<td style=\"text-align:right\">".number_format($inputQuantity,0)."</td>";
                    echo "<td style=\"text-align:right\">".$this->fmt->formatAsM3($inputVolume)."</td>";
                    echo "<td style=\"text-align:right\">".$this->fmt->formatAsISK($inputCost)."</td></tr>";
                }
            }
        }
        else {
            foreach ($inputs as $input) {
                $typeID = $input['typeID'];
                $type = new Type($typeID);
                $price = $type->getPrice($this->systemID,$this->datetime,$this->inputPrice);
                $inputQuantity = $input['inputQty']*$numCycles;
                $itemVolume = $type->getVolume();
                $inputVolume = $itemVolume*$inputQuantity;
                $inputCost = $price*$inputQuantity;
                $totalQuantity += $inputQuantity;
                $totalVolume += $inputVolume;
                $totalCost += $inputCost;
                $itemName = $type->getName();
                $itemDesc = htmlspecialchars($type->getDescription());
                $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
                $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}<br><br>Unit Volume: ".$this->fmt->formatAsM3($itemVolume)."<br>{$priceBoundary} {$this->systemName} {$priceType} Price: ".$this->fmt->formatAsISK($price);
                echo "<tr><td><a data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" title=\"".$itemName."\" data-content=\"".htmlspecialchars($mouseoverText)."\">{$itemName}</a></td>";
                echo "<td style=\"text-align:right\">".number_format($inputQuantity,0)."</td>";
                echo "<td style=\"text-align:right\">".$this->fmt->formatAsM3($inputVolume)."</td>";
                echo "<td style=\"text-align:right\">".$this->fmt->formatAsISK($inputCost)."</td></tr>";
            }
        }
        echo "</tbody><thead><tr><th>Total</th>";
        echo "<th style=\"text-align:right\">".number_format($totalQuantity,0)."</th>";
        echo "<th style=\"text-align:right\">".$this->fmt->formatAsM3($totalVolume)."</th>";
        echo "<th style=\"text-align:right\">".$this->fmt->formatAsISK($totalCost)."</th></tr>";
        echo "</thead></table>";
    }

    public function generateIntermediateTable() {
        $intermediates = $this->reaction->getInputs();
        $numCycles = $this->calc->getNumCycles();
        $totalQuantity = 0;
        $totalVolume = 0;
        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Material</th><th>Quantity</th><th>Volume</th></tr></thead><tbody>";
        foreach ($intermediates as $intermediate) {
            $typeID = $intermediate['typeID'];
            $type = new Type($typeID);
            $intermediateQuantity = $intermediate['inputQty']*$numCycles*2;
            $itemVolume = $type->getVolume();
            $intermediateReaction = new Reaction($this->db->getReactionIDFromTypeID($typeID,0));
            $intermediateVolume = $itemVolume*$intermediateReaction->getOutputQty()*$numCycles;
            $totalQuantity += $intermediateQuantity;
            $totalVolume += $intermediateVolume;
            $itemName = $type->getName();
            $itemDesc = htmlspecialchars($type->getDescription());
            $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
            $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}<br><br>Unit Volume: ".$this->fmt->formatAsM3($itemVolume);
            echo "<tr><td><a data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" title=\"".$itemName."\" data-content=\"".htmlspecialchars($mouseoverText)."\">{$itemName}</a></td>";
            echo "<td style=\"text-align:right\">".number_format($intermediateQuantity,0)."</td>";
            echo "<td style=\"text-align:right\">".$this->fmt->formatAsM3($intermediateVolume)."</td></tr>";
        }
        echo "</tbody><thead><tr><th>Total</th>";
        echo "<th style=\"text-align:right\">".number_format($totalQuantity,0)."</th>";
        echo "<th style=\"text-align:right\">".$this->fmt->formatAsM3($totalVolume)."</th></tr></thead></table>";
    }

    public function generateOutputTable() {
        $numCycles = $this->calc->getNumCycles();
        if ($this->outputPrice == "b"):
            $priceBoundary = "Highest";
            $priceType = "Buy";
        else:
            $priceBoundary = "Lowest";
            $priceType = "Sell";
        endif;
        $type = $this->reaction->getOutput();
        $typeID = $type->getTypeID();
        $price = $type->getPrice($this->systemID,$this->datetime,$this->outputPrice);
        $quantity = $this->calc->getHourlyOutputVolume();
        $totalOutputQty = round($quantity*$numCycles);
        $itemVolume = $type->getVolume();
        $outputVolume = $itemVolume*$quantity;
        $totalOutputVolume = $outputVolume*$numCycles;
        $totalRevenue = $this->calc->getHourlyRevenue()*$numCycles;
        $itemName = $type->getName();
        $itemDesc = htmlspecialchars($type->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}<br><br>Unit Volume: ".$this->fmt->formatAsM3($itemVolume)."<br>{$priceBoundary} {$this->systemName} {$priceType} Price: ".$this->fmt->formatAsISK($price);
        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Product</th><th>Quantity</th><th>Volume</th><th>Revenue</th></tr></thead>";
        echo "<tbody><tr><td><a data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" title=\"".$itemName."\" data-content=\"".htmlspecialchars($mouseoverText)."\">{$itemName}</a></td><td style=\"text-align:right\">".number_format($totalOutputQty,0)."</td>";
        echo "<td style=\"text-align:right\">".$this->fmt->formatAsM3($totalOutputVolume)."</td>";
        echo "<td style=\"text-align:right\">".$this->fmt->formatAsISK($totalRevenue)."</td></tr></tbody></table>";
    }

    public function generateFuelTable() {
        $numCycles = $this->calc->getNumCycles();
        if ($this->fuelPrice == "b"):
            $priceBoundary = "Highest";
            $priceType = "Buy";
        else:
            $priceBoundary = "Lowest";
            $priceType = "Sell";
        endif;
        $typeID = $this->db->getFuelBlockID($this->race);
        $type = new Type($typeID);
        $itemName = $type->getName();
        $fuelBlockVolume = $this->calc->getHourlyFuelVolume()*$numCycles;
        $quantity = ($fuelBlockVolume)/5;
        $price = $type->getPrice($this->systemID,$this->datetime,$this->fuelPrice);
        $fuelBlockCost = $price*$quantity;
        $itemDesc = htmlspecialchars($type->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}<br><br>Unit Volume: ".$this->fmt->formatAsM3(5)."<br>{$priceBoundary} {$this->systemName} {$priceType} Price: ".$this->fmt->formatAsISK($price);
        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Fuel Type</th><th>Quantity</th><th>Volume</th><th>Cost</th></tr></thead>";
        echo "<tbody><tr><td><a data-toggle=\"popover\" data-trigger=\"hover\" data-placement=\"bottom\" title=\"".$itemName."\" data-content=\"".htmlspecialchars($mouseoverText)."\">{$itemName}s</a></td><td style=\"text-align:right\">".number_format($quantity,0)."</td>";
        echo "<td style=\"text-align:right\">".$this->fmt->formatAsM3($fuelBlockVolume)."</td>";
        echo "<td style=\"text-align:right\">".$this->fmt->formatAsISK($fuelBlockCost)."</td></tr></tbody></table>";
    }

    public function generateTowerList() {
        $fittingURL = "http://eve.1019.net/pos/index.php";
        $reactionType = $this->reaction->getReactionType();
        switch ($this->race) {
            case 1:
                break;
            case 2:
                $ltParam = "ct=03";
                $mtParam = "ct=04";
                $largeSimpleURL = $fittingURL."?ct=03&mod=1C1C04040404040404040O0W&sov={$this->sov}";
                $largeComboURL = $fittingURL."?ct=03&mod=1C0H040404040404&sov={$this->sov}";
                $largeComplexURL = $fittingURL."?ct=03&mod=0H040404040404040404&sov={$this->sov}";
                $mediumPolymerURL = $fittingURL."?ct=04&mod=45041R1R440W0O0W0U&sov={$this->sov}";
                $mediumSimpleURL = $fittingURL."?ct=04&mod=1C040404040W&sov={$this->sov}";
                break;
            case 3:
                $ltParam = "ct=06";
                $mtParam = "ct=07";
                $largeSimpleURL = $fittingURL."?ct=06&mod=1C1C0404040404040W0O&sov={$this->sov}";
                $largeComplexURL = $fittingURL."?ct=06&mod=0H040404040404040W&sov={$this->sov}";
                $mediumPolymerURL = $fittingURL."?ct=07&mod=45041R1R440O0W&sov={$this->sov}";
                $mediumSimpleURL = $fittingURL."?ct=07&mod=1C040404&sov={$this->sov}";
                break;
            case 4:
                break;
        }

        $numTowers = $this->calc->getNumTowers();
        $numLarge = $numTowers - $numTowers%1;
        $medium = false;
        if (fmod($numTowers,1) != 0) {
            $medium = true;
        }
        $numComplex = 0;
        if ($this->chain) {
            $numComplex = 2;
        }
        $numSimple = $numLarge - $numComplex;

        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Tower</th><th>Size</th><th>Produces</th><th>Reactors</th></tr></thead><tbody>";
        if ($reactionType == 3) {
            echo "<tr><td><a href='{$mediumSimpleURL}'>Alchemy #1</a></td><td>Medium</td><td>Unrefined Product</td><td>1 Simple</td></tr>";
        } elseif ($reactionType == 4) {
            echo "<tr><td><a href='{$mediumPolymerURL}'>Polymer #1</a></td><td>Medium</td><td>Polymer</td><td>1 Polymer</td></tr>";
        } else {
            for ($x = 1; $x <= $numSimple; $x++) {
                echo "<tr><td><a href='{$largeSimpleURL}' target='_blank'>Simple #{$x}</a></td><td>Large</td><td>Intermediates</td><td>2 Simple</td></tr>";
            }
            if ($medium) {
                echo "<tr><td><a href='{$mediumSimpleURL}'>Simple #{$x}</a></td><td>Medium</td><td>Intermediate</td><td>1 Simple</td></tr>";
            }
            for ($x = 1; $x <= $numComplex; $x++) {
                if ($this->race == 2 && $numSimple == 0) {
                    echo "<tr><td><a href='{$largeComboURL}' target='_blank'>Combo #{$x}</a></td><td>Large</td><td>Both</td><td>1 Complex, 1 Simple</td></tr>";
                }
                else {
                    echo "<tr><td><a href='{$largeComplexURL}' target='_blank'>Complex #{$x}</a></td><td>Large</td><td>Composite</td><td>1 Complex</td></tr>";
                }
            }
        }
        echo "</tbody></table>";
    }

    public function generateShippingReport() {
        switch ($this->timeframe) {
            case "d":
                $timeframeStr = "Daily";
                break;
            case "w":
                $timeframeStr = "Weekly";
                break;
            case "m":
                $timeframeStr = "Monthly";
                break;
        }
        $numCycles = $this->calc->getNumCycles();
        $fuelBlock = new Type($this->db->getFuelBlockID($this->race));
        $fuelBlockName = $fuelBlock->getName();
        $fuelBlockVolume = $this->calc->getHourlyFuelVolume();
        $totalFuelBlockVolume = $fuelBlockVolume*$numCycles;
        $totalFuelBlockShippingCost = $totalFuelBlockVolume*300;
        $inputVolume = $this->calc->getHourlyInputVolume();
        $totalInputVolume = round($inputVolume*$numCycles);
        $totalInputShippingCost = $totalInputVolume*300;
        $outputVolume = $this->calc->getHourlyOutputVolume();
        $totalOutputVolume = round($outputVolume*$numCycles);
        $totalOutputShippingCost = $totalOutputVolume*300;
        $totalVolume = $totalFuelBlockVolume + $totalInputVolume + $totalOutputVolume;
        $totalShippingCost = $totalFuelBlockShippingCost + $totalInputShippingCost + $totalOutputShippingCost;

        echo "<table class=\"table table-condensed table-striped table-bordered\"><thead>";
        echo "<tr><th>Category</th><th>Volume</th><th>Shipping Cost</th></tr></thead>";
        echo "<tbody><tr><td>{$fuelBlockName}s</td><td style=\"text-align:right\">".$this->fmt->formatAsM3($totalFuelBlockVolume)."</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($totalFuelBlockShippingCost)."</td></tr>";
        echo "<tr><td>Inputs</td><td style=\"text-align:right\">".$this->fmt->formatAsM3($totalInputVolume)."</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($totalInputShippingCost)."</td></tr>";
        echo "<tr><td>Outputs</td><td style=\"text-align:right\">".$this->fmt->formatAsM3($totalOutputVolume)."</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($totalOutputShippingCost)."</td></tr></tbody>";
        echo "<thead><tr><th>Total</th><th style=\"text-align:right\">".$this->fmt->formatAsM3($totalVolume)."</th><th style=\"text-align:right\">".$this->fmt->formatAsISK($totalShippingCost)."</th></tr></thead></table>";
    }

    public function generateIncomeStatement() {
        switch ($this->timeframe) {
            case "d":
                $timeframeStr = "Daily";
                break;
            case "w":
                $timeframeStr = "Weekly";
                break;
            case "m":
                $timeframeStr = "Monthly";
                break;
        }
        $reactionType = $this->reaction->getReactionType();
        if ($this->chain) {
            if ($reactionType == 1) {
                $this->chain = 0;
            }
            else {
                $chainStr = " Chain";
            }
        }

        $numCycles = $this->calc->getNumCycles();
        $numTowers = $this->calc->getNumTowers();
        $revenue = $this->calc->getHourlyRevenue();
        $totalIncome = $revenue;
        $inputCost = $this->calc->getHourlyInputCost();
        $inputVolume = round($this->calc->getHourlyInputVolume());
        $outputVolume = round($this->calc->getHourlyOutputVolume());
        $fuelBlock = new Type($this->db->getFuelBlockID($this->race));
        $fuelBlockName = $fuelBlock->getName();
        $fuelVolume = $this->calc->getHourlyFuelVolume();
        $fuelCost = $this->calc->getHourlyFuelCost();
        $inputBrokerFee = 0;
        if ($this->inputPrice == "b") {
            $inputBrokerFee = $inputCost*$this->brokerFee;
        }
        $outputBrokerFee = 0;
        if ($this->outputPrice == "s") {
            $outputBrokerFee = $revenue*$this->brokerFee;
        }
        $fuelBrokerFee = 0;
        if ($this->fuelPrice == "b") {
            $fuelBrokerFee = $fuelCost*$this->brokerFee;
        }
        $gsfMoonTax = 0;
        if ($this->gsf) {
            $gsfMoonTax = (1000000/24) * ceil($numTowers);
        }
        $outputSalesTax = $revenue*$this->salesTax;
        $totalTaxAndFees = $inputBrokerFee + $fuelBrokerFee + $outputBrokerFee + $outputSalesTax + $gsfMoonTax;
        $totalShipping = round(($inputVolume+$outputVolume+$fuelVolume)*300);
        $totalExpenses = $inputCost + $fuelCost + $totalShipping;
        $profit = $totalIncome - ($totalExpenses + $totalTaxAndFees);
        $profitMargin = round($profit/$totalIncome,4)*100;

        echo "<table class=\"table table-condensed table-striped table-bordered\">";
        echo "<thead>";
        echo "<tr><th colspan='2'>Revenue</th></tr></thead>";
        echo "<tbody><tr><td>Production</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($revenue*$numCycles)."</td></tr>";
        echo "<tr><th>Total Revenue</th><th style=\"text-align:right\">".$this->fmt->formatAsISK($totalIncome*$numCycles)."</th></tr></tbody>";
        echo "<thead><tr><th colspan='2'><b>Expenses</b></th></tr></thead>";
        echo "<tbody><tr><td>{$fuelBlockName}s</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($fuelCost*$numCycles)."</td></tr>";
        echo "<tr><td>Fuel Block Shipping</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($fuelVolume*$numCycles*300)."</td></tr>";
        echo "<tr><td>Input Materials</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($inputCost*$numCycles)."</td></tr>";
        echo "<tr><td>Input Shipping</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($inputVolume*300*$numCycles)."</td></tr>";
        echo "<tr><td>Output Shipping</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($outputVolume*300*$numCycles)."</td></tr>";
        echo "<tr><th>Total Expenses</th><th style=\"text-align:right\">".$this->fmt->formatAsISK($totalExpenses*$numCycles)."</th></tr></tbody>";
        echo "<thead><tr><th colspan='2' align=left><b>Sales Tax and Broker's Fees</b></th></tr></thead>";
        if ($this->fuelPrice == "b") {
            echo "<tbody><tr><td>Fuel Block Broker's Fee (".($this->brokerFee*100)."%)</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($fuelBrokerFee*$numCycles)."</td></tr>";
        }
        if ($this->inputPrice == "b") {
            echo "<tr><td>Input Broker's Fee (" . ($this->brokerFee * 100) . "%)</td><td style=\"text-align:right\">" . $this->fmt->formatAsISK($inputBrokerFee * $numCycles) . "</td></tr>";
        }
        if ($this->outputPrice == "s") {
            echo "<tr><td>Output Broker's Fee (" . ($this->brokerFee * 100) . "%)</td><td style=\"text-align:right\">" . $this->fmt->formatAsISK($outputBrokerFee * $numCycles) . "</td></tr>";
        }
        echo "<tr><td>Output Sales Tax (".($this->salesTax*100)."%)</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($outputSalesTax*$numCycles)."</td></tr>";
        if ($this->gsf) {
            echo "<tr><td>GSF Moon Tax</td><td style=\"text-align:right\">".$this->fmt->formatAsISK($gsfMoonTax*$numCycles)."</td></tr>";
        }
        echo "</tbody><thead><tr><th>Total Tax and Fees</th><th style=\"text-align:right\">".$this->fmt->formatAsISK($totalTaxAndFees*$numCycles)."</th></tr>";
        if ($profitMargin >= 10) {
            $class = "success";
        }
        else if ($profitMargin > 0) {
            $class = "warning";
        }
        else {
            $class = "danger";
        }
        echo "<tr><th align=left>Profit</th><th class='$class' style=\"text-align:right\">".$this->fmt->formatAsISK($profit*$numCycles)."</th></tr>";
        echo "<tr><th align=left>Profit Margin</th><th class='$class' style=\"text-align:right\">{$profitMargin}%</th></tr></thead>";
        echo "</table>";
    }

    public function getReactionName() {
        return $this->reaction->getOutput()->getName();
    }

} 
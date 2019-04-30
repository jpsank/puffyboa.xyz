<?php

class Equation {
    public $reactants;
    public $products;

    function __construct($reactants, $products) {
        $this->reactants = $reactants;
        $this->products = $products;
    }

    function countConstituents($molecules) {
        $dict = [];
        foreach ($molecules as $mol) {
            foreach ($mol->countConstituents() as $sym=>$num) {
                if (!array_key_exists($sym, $dict)) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $num;
            }
        }
        return $dict;
    }
    function isBalanced() {
        $left = $this->countConstituents($this->reactants);
        $right = $this->countConstituents($this->products);
        return ($left==$right);
    }

    // BALANCING CHEMICAL EQUATIONS
    function getBalancedEq() {

        $dict = [];
        $len1 = sizeof($this->reactants);
        $molecules = array_merge($this->reactants,$this->products);
        foreach ($molecules as $i=>$mol) {
            foreach ($mol->countConstituents(1) as $sym=>$num) {
                if (!array_key_exists($sym, $dict)) {
                    $dict[$sym] = array_fill(0,sizeof($molecules),0);
                }
                if ($i < $len1) {  // molecule is a reactant
                    $dict[$sym][$i] = -$num;
                } else {  // molecule is a product
                    $dict[$sym][$i] = $num;
                }
            }
        }
        $matrix = array_values($dict);

        $balanced = array_map(function($x) { return abs($x); }, $this->balance($matrix));
        // convert into whole number coefficients
        for ($i=0; $i<count($balanced); $i++) {
            $m = float2rat($balanced[$i])[1];
            if ($m > 1) {
                for ($j=0; $j<count($balanced); $j++) {
                    $balanced[$j] *= $m;
                }
            }
        }

        $newEq = new Equation(deepCopy($this->reactants), deepCopy($this->products));
        $numReactants = count($newEq->reactants);
        foreach ($balanced as $i=>$n) {
            if ($i < $numReactants) {
                $newEq->reactants[$i]->num = $n;
            } else {
                $newEq->products[$i-$numReactants]->num = $n;
            }
        }
        return $newEq;
    }
    function balance($matrix) {
        $reduced = rref($matrix);

        $y = [];
        foreach ($reduced as $m) {
            array_push($y, $m[sizeof($m)-1]);
        }
        while (end($y) == 0) {
            array_pop($y);
        }
        array_push($y,1);
        return $y;
    }

    function getEquationStr($html=false, $format=false) {
        $callback = function($mol) use ($html, $format) {
            return $mol->getFullFormula($html, $format);
        };
        $left = join(" + ", array_map($callback, $this->reactants));
        $right = join(" + ", array_map($callback, $this->products));
        if ($format) {
            return "$left &rarr; $right";
        } else {
            return "$left = $right";
        }
    }

    function checkHasSupport() {
        foreach (array_merge($this->reactants,$this->products) as $mol) {
            if (!$mol->appendices) {
                return false;
            }
        }
        return true;
    }

    function getEnthalpy() {
        $answer = 0;
        foreach ($this->reactants as $mol) {
            if ($mol->getEnthalpy() === false) {return "N/A";}
            $answer -= $mol->num * $mol->getEnthalpy();
        }
        foreach ($this->products as $mol) {
            if ($mol->getEnthalpy() === false) {return "N/A";}
            $answer += $mol->num * $mol->getEnthalpy();
        }
        return $answer;
    }
    function getEntropy() {
        $answer = 0;
        foreach ($this->reactants as $mol) {
            if ($mol->getEntropy() === false) {return "N/A";}
            $answer -= $mol->num * $mol->getEntropy();
        }
        foreach ($this->products as $mol) {
            if ($mol->getEntropy() === false) {return "N/A";}
            $answer += $mol->num * $mol->getEntropy();
        }
        return $answer;
    }
    function getGibbs() {
        $answer = 0;
        foreach ($this->reactants as $mol) {
            if ($mol->getGibbs() === false) {return "N/A";}
            $answer -= $mol->num * $mol->getGibbs();
        }
        foreach ($this->products as $mol) {
            if ($mol->getGibbs() === false) {return "N/A";}
            $answer += $mol->num * $mol->getGibbs();
        }
        return $answer;
    }

    function getActivationTemp() {
        $enthalpy = $this->getEnthalpy();
        $entropy = $this->getEntropy();
        return $enthalpy/($entropy/1000);  // convert entropy to kJ to match enthalpy
    }
    function isFavorableAt($kelvin) {
        $enthalpy = $this->getEnthalpy();
        $entropy = $this->getEntropy();
        if ($enthalpy-$kelvin*($entropy/1000) < 0) {
            return true;
        }
        return false;
    }

    function getEnthalpyBehavior() {
        $enthalpy = $this->getEnthalpy();
        if ($enthalpy === false) {
            return "N/A";
        }
        if ($enthalpy > 0) {
            return "endothermic";
        } else if ($enthalpy < 0) {
            return "exothermic";
        } else {
            return "thermoneutral";
        }
    }
    function getGibbsBehavior() {
        $gibbs = $this->getGibbs();
        if ($gibbs === false) {
            return "N/A";
        }
        if ($gibbs > 0) {
            return "endergonic";
        } else if ($gibbs < 0) {
            return "exergonic";
        } else {
            return "at equilibrium";
        }
    }
    function getEntropyBehavior() {
        $entropy = $this->getEntropy();
        if ($entropy === false) {
            return "N/A";
        }
        if ($entropy > 0) {
            return "increase disorder";
        } else if ($entropy < 0) {
            return "increase order";
        } else {
            return "no change";
        }
    }
    function getBehavior() {
        $enthalpy = $this->getEnthalpy();
        $entropy = $this->getEntropy();
        if ($enthalpy && $entropy) {
            $activationTemp = round($this->getActivationTemp(), 3);
            if ($enthalpy > 0 && $entropy > 0) {
                return "spontaneous at high temperatures (above " . $activationTemp . "K)";
            } else if ($enthalpy < 0 && $entropy < 0) {
                return "spontaneous at low temperatures (below " . $activationTemp . "K)";
            } else if ($enthalpy > 0 && $entropy < 0) {
                return "never spontaneous";
            } else if ($enthalpy < 0 && $entropy > 0) {
                return "always spontaneous";
            }
        }
        return "N/A";
    }

}

function rref($matrix)
{
    $lead = 0;
    $rowCount = count($matrix);
    if ($rowCount == 0)
        return $matrix;
    $columnCount = 0;
    if (isset($matrix[0])) {
        $columnCount = count($matrix[0]);
    }
    for ($r = 0; $r < $rowCount; $r++) {
        if ($lead >= $columnCount)
            break;
        {
            $i = $r;
            while ($matrix[$i][$lead] == 0) {
                $i++;
                if ($i == $rowCount) {
                    $i = $r;
                    $lead++;
                    if ($lead == $columnCount)
                        return $matrix;
                }
            }
            $temp = $matrix[$r];
            $matrix[$r] = $matrix[$i];
            $matrix[$i] = $temp;
        }        {
            $lv = $matrix[$r][$lead];
            for ($j = 0; $j < $columnCount; $j++) {
                $matrix[$r][$j] = $matrix[$r][$j] / $lv;
            }
        }
        for ($i = 0; $i < $rowCount; $i++) {
            if ($i != $r) {
                $lv = $matrix[$i][$lead];
                for ($j = 0; $j < $columnCount; $j++) {
                    $matrix[$i][$j] -= $lv * $matrix[$r][$j];
                }
            }
        }
        $lead++;
    }
    return $matrix;
}

function deepCopy($arr) {
    $arr2 = [];
    foreach ($arr as $item) {
        if (is_object($item)) {
            array_push($arr2,clone $item);
        } else if (is_array($item)) {
            array_push($arr2,deepCopy($item));
        } else {
            array_push($arr2,$item);
        }
    }
    return $arr2;
}

function float2rat($n, $tolerance = 1.e-6) {
    $h1=1; $h2=0;
    $k1=0; $k2=1;
    $b = 1/$n;
    do {
        $b = 1/$b;
        $a = floor($b);
        $aux = $h1; $h1 = $a*$h1+$h2; $h2 = $aux;
        $aux = $k1; $k1 = $a*$k1+$k2; $k2 = $aux;
        $b = $b-$a;
    } while (abs($n-$h1/$k1) > $n*$tolerance);

    return [$h1, $k1];
}
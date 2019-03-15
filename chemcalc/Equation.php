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
                if (!$dict[$sym]) {
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

    function countNewConstituents($molecules, $nums) {
        $dict = [];
        foreach ($molecules as $i=>$mol) {
            foreach ($mol->countConstituents($nums[$i]) as $sym=>$num) {
                if (!$dict[$sym]) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $num;
            }
        }
        return $dict;
    }
    function getBalancedEq() {
        $numReactants = sizeof($this->reactants);
        $numProducts = sizeof($this->products);
        $bruteForceMax = 10;
        // adjust max brute force ratio so we don't use too much processing power
        while ($bruteForceMax > 1 && pow($bruteForceMax, $numReactants+$numProducts) > 50000) {
            $bruteForceMax--;
        }
        foreach(sampling(range(1,$bruteForceMax), $numReactants+$numProducts) as $ratioArray) {
            $leftRatio = array_slice($ratioArray,0,$numReactants);
            $rightRatio = array_slice($ratioArray,$numReactants);
            $leftDict = $this->countNewConstituents($this->reactants,$leftRatio);
            $rightDict = $this->countNewConstituents($this->products,$rightRatio);
            if ($leftDict==$rightDict) {
                $newEq = clone $this;
                $newEq->reactants = array_map(function ($mol) { return clone $mol; }, $this->reactants);
                $newEq->products = array_map(function ($mol) { return clone $mol; }, $this->products);
                foreach ($ratioArray as $i=>$n) {
                    if ($i < $numReactants) {
                        $newEq->reactants[$i]->num = $n;
                    } else {
                        $newEq->products[$i-$numReactants]->num = $n;
                    }
                }
                return $newEq;
            }
        }
        return false;
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

function sampling($items, $size, $combinations = array()) {
    if (empty($combinations)) {
        $combinations = $items;
    }
    if ($size == 1) {
        return $combinations;
    }
    $new_combinations = array();
    foreach ($combinations as $combination) {
        foreach ($items as $item) {
            if (is_array($combination)) {
                $new_combinations[] = array_merge($combination,[$item]);
            } else {
                $new_combinations[] = [$combination,$item];
            }
        }
    }
    return sampling($items, $size - 1, $new_combinations);

}
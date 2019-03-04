<?php

class Equation {
    public $reactants;
    public $products;

    function __construct($equation) {
        $parsed = $this->parseEqStr($equation);
        $this->reactants = $parsed[0];
        $this->products = $parsed[1];
    }

    function parseSide($side) {
        $list = [];
        foreach ($side as $item) {
            $item = trim($item);
            array_push($list, new Molecule($item));
        }
        return $list;
    }
    function parseEqStr($string) {
        $sides = explode("=", $string);
        $left = explode("+",$sides[0]);
        $right = explode("+",$sides[1]);
        $reactants = $this->parseSide($left);
        $products = $this->parseSide($right);
        return [$reactants, $products];
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

    function getBreakdown() {
        $left = [];
        foreach ($this->reactants as $mol) {
            array_push($left,$mol->getFormulaHTML(true));
        }
        $left = join(" + ", $left);
        $right = [];
        foreach ($this->products as $mol) {
            array_push($right,$mol->getFormulaHTML(true));
        }
        $right = join(" + ", $right);
        return "$left &rarr; $right";
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
        if ($enthalpy > 0 && $entropy > 0) {
            return "spontaneous at high temperatures";
        } else if ($enthalpy < 0 && $entropy < 0) {
            return "spontaneous at low temperatures";
        } else if ($enthalpy > 0 && $entropy < 0) {
            return "never spontaneous";
        } else if ($enthalpy < 0 && $entropy > 0) {
            return "always spontaneous";
        }
        return "N/A";
    }

}
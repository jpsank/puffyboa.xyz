<?php

class Molecule {
    public $charge;
    public $appendices;
    public $phase;
    public $constituents;
    public $num;

    function __construct($formula) {
        global $ELEMENTS, $APPENDIX;

        // Get num of moles
        preg_match("/(^\d*)\s?(.+)/", $formula, $matches);
        if ($matches[1]) {
            $formula = $matches[2];
            $this->num = (int)$matches[1];
        } else {
            $this->num = 1;
        }

        // Get phase specified

        $phase = null;
        $pattern = "/\s?(\((g|l|s|aq|a|c).*\))$/";
        preg_match($pattern, $formula, $matches, PREG_OFFSET_CAPTURE);
        if ($matches) {
            $phase = $matches[1][0];
            $formula = substr($formula,0,$matches[0][1]);
        }

        // If formula not in appendix, try to find closest match
        if ($APPENDIX[$formula] === null) {
            foreach (array_keys($APPENDIX) as $m) {
                if (startsWith($m, $formula)) {
                    $formula = $m;
                    break;
                }
            }
        }
        $this->appendices = $APPENDIX[$formula];

        // Get appendix properties for phase, if specified

        if ($this->appendices) {
            if ($phase != null) {
                foreach ($this->appendices as $key=>$val) {
                    if ($key == $phase) {
                        $this->phase = $key;
                        break;
                    }
                }
                if (!$this->phase) {
                    foreach ($this->appendices as $key=>$val) {
                        if (startsWith($key, substr($phase, 0, -1))) {
                            $this->phase = $key;
                            break;
                        }
                    }
                }
            }
            if ($this->phase == null) {
                $this->phase = key($this->appendices);
            }
        }

        // Get charge of molecule, if specified

        $this->charge = 0;
        if (endsWith($formula, "-")) {
            $this->charge = -1;
        } else if (endsWith($formula, "+")) {
            $this->charge = 1;
        } else {
            $pos = strpos($formula, "-");
            if ($pos) {
                $this->charge = (int)substr($formula, $pos);
                $formula = substr($formula, 0, $pos);
            } else {
                $pos = strpos($formula, "+");
                if ($pos) {
                    $this->charge = (int)substr($formula, $pos);
                    $formula = substr($formula, 0, $pos);
                }
            }
        }

        // Divide molecule into constituent atoms

        $this->constituents = [];
        $pattern = join("|",array_keys($ELEMENTS));
        preg_match_all("/(?:$pattern)\d*|\((?:(?:$pattern)\d*)+\)\d*/", $formula, $matches);
        foreach ($matches[0] as $match) {
            array_push($this->constituents, new Polyatomic($match));
        }
    }

    function getFlatFormula() {
        $string = "";
        foreach ($this->constituents as $constituent) {
            $string .= $constituent->getName(false);
        }
        if ($this->charge > 0) {
            $string .= "+$this->charge";
        } else if ($this->charge < 0) {
            $string .= "$this->charge";
        }
        return $string;
    }
    function getFormula() {
        $string = "";
        foreach ($this->constituents as $constituent) {
            $string .= $constituent->getName();
        }
        if ($this->charge > 0) {
            $string .= "<sup>+$this->charge</sup>";
        } else if ($this->charge < 0) {
            $string .= "<sup>$this->charge</sup>";
        }
        return $string;
    }
    function getFormulaHTML($format=false) {
        $num = ($this->num!=1)? $this->num : "";
        $formula = $this->getFormula();
        $phase = $this->getCurrentPhase();
        $phase = ($phase)? " $phase": "";

        $string = $num . $formula . $phase;

        if ($format) {
            $encoded = urlencode($this->getFlatFormula() . $phase);
            if ($phase != "") {
                $string = "<a class='formula' href='?input=$encoded'>$string</a>";
            } else {
                $string = "<a class='formula unknown' href='?input=$encoded'>$string (N/A)</a>";
            }
        }

        return $string;
    }

    function getPhases() {
        if ($this->appendices) {
            return array_keys($this->appendices);
        }
        return [];
    }
    function getPhasesHTML() {
        $phases = $this->getPhases();
        if ($phases) {
            $html = "";
            foreach ($phases as $phase) {
                $encoded = urlencode($this->getFlatFormula() . " " . $phase);
                if ($phase === $this->phase) {
                    $html .= "<a class='phase selected' href='?input=$encoded'>$phase</a> ";
                } else {
                    $html .= "<a class='phase' href='?input=$encoded'>$phase</a> ";
                }
            }
            return $html;
        } else {
            return "N/A";
        }
    }

    function getEnthalpy() {
        if ($this->appendices) {
            return $this->appendices[$this->phase]["enthalpy"];
        }
        return false;
    }
    function getEntropy() {
        if ($this->appendices) {
            return $this->appendices[$this->phase]["entropy"];
        }
        return false;
    }
    function getGibbs() {
        if ($this->appendices) {
            return $this->appendices[$this->phase]["gibbs"];
        }
        return false;
    }
    function getCurrentPhase() {
        if ($this->appendices) {
            return $this->phase;
        }
        return null;
    }

    function getMass() {
        $mm = 0;
        foreach ($this->constituents as $constituent) {
            $mm += $constituent->getMass()*$constituent->num;
        }
        return $mm;
    }

    function getComposition() {
        $props = [];
        foreach ($this->constituents as $constituent) {
            $name = $constituent->getName();
            $atomic_mass = $constituent->getMass();
            array_push($props, "($name " . $atomic_mass . "g x $constituent->num)");
        }
        $string = join(" + ", $props);
        return $string;
    }

    function countConstituents() {
        $dict = [];
        foreach($this->constituents as $constituent) {
            foreach ($constituent->countParticles() as $sym=>$num) {
                if (!$dict[$sym]) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $num * $this->num;
            }
        }
        return $dict;
    }

}
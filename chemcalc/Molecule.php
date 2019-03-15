<?php

class Molecule {
    public $num;
    public $constituents;
    public $charge;
    public $phase;
    public $appendices;

    function __construct($num, $constituents, $charge, $phase, $appendices) {
        $this->num = $num;
        $this->constituents = $constituents;
        $this->charge = $charge;
        $this->phase = $phase;
        $this->appendices = $appendices;
    }

    function getFormula($format=true) {
        $string = "";
        foreach ($this->constituents as $constituent) {
            $string .= $constituent->getName($format);
        }
        if ($format) {
            if ($this->charge > 0) {
                $string .= "<sup>+$this->charge</sup>";
            } else if ($this->charge < 0) {
                $string .= "<sup>$this->charge</sup>";
            }
        } else {
            if ($this->charge > 0) {
                $string .= "+$this->charge";
            } else if ($this->charge < 0) {
                $string .= "$this->charge";
            }
        }
        return $string;
    }
    function getFullFormula($html=false, $format=true) {
        $num = ($this->num!=1)? $this->num : "";
        $formula = $this->getFormula($format);
        $phase = $this->getCurrentPhase();
        $phase = ($phase)? " $phase": "";

        $string = $num . $formula . $phase;

        if ($html) {
            $encoded = urlencode($this->getFormula(false) . $phase);
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
                $encoded = urlencode($this->getFormula(false) . " " . $phase);
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

    function countConstituents($molNum=null) {
        if ($molNum == null) {
            $molNum = $this->num;
        }
        $dict = [];
        foreach($this->constituents as $constituent) {
            foreach ($constituent->countParticles() as $sym=>$num) {
                if (!$dict[$sym]) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $num * $molNum;
            }
        }
        return $dict;
    }

}
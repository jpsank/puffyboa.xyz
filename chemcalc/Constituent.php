<?php

class Atom {
    public $elem;
    public $num = 1;

    function __construct($sym) {
        global $ELEMENTS;
        $this->elem = $ELEMENTS[$sym];
    }

    function getName() {
        return $this->elem["symbol"];
    }
    function getMass() {
        return $this->elem['atomic_mass'];
    }
    function countParticles() {
        return [$this->elem["symbol"]=>$this->num];
    }

}

class Constituent {
    public $particles;
    public $num;

    function __construct($num, $particles) {
        $this->num = $num;
        $this->particles = $particles;
    }

    function getName($format=true) {  // $format specifies if subscript html should be used in name
        $string = "";
        foreach($this->particles as $particle) {
            $string .= $particle->getName($format);
        }
        if (sizeof($this->particles) > 1) {
            $string = "($string)";
        }
        if ($format) {
            $string .= ($this->num == 1) ? "" : "<sub>$this->num</sub>";
        } else {
            $string .= ($this->num == 1) ? "" : $this->num;
        }
        return $string;
    }
    function getMass() {
        $mass = 0;
        foreach($this->particles as $particle) {
            $mass += $particle->getMass() * $particle->num;
        }
        return $mass;
    }
    function countParticles() {
        $dict = [];
        foreach($this->particles as $particle) {
            foreach ($particle->countParticles() as $sym=>$num) {
                if (!$dict[$sym]) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $num * $this->num;
            }
        }
        return $dict;
    }

}
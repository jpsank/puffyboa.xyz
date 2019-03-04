<?php

class Constituent {

}

class Atom extends Constituent {
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

class Polyatomic extends Constituent {
    public $particles;
    public $num;

    function __construct($string) {
        global $ELEMENTS;

        $this->particles = [];
        $pattern = join("|",array_keys($ELEMENTS));

        if (startsWith($string, "(")) {  // it is polyatomic (i.e. (NO3)2 or (OH)2)
            preg_match("/^(.+?)(\d*)$/", $string, $match);
            $this->num = ($match[2] == "") ? 1 : (int)$match[2];
            $poly = substr($match[1], 1, -1);

            preg_match_all("/(?:$pattern)\d*/", $poly, $matches);
            foreach ($matches[0] as $match) {
                array_push($this->particles, new Polyatomic($match));
            }
        } else {  // it is a single element (i.e. C or N2)
            preg_match("/^(.+?)(\d*)$/", $string, $match);
            $this->num = ($match[2] == "") ? 1 : (int)$match[2];
            $sym = $match[1];
            $atom = new Atom($sym);
            array_push($this->particles, $atom);
        }
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
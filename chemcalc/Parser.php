<?php

class Parser {
    function __construct() {
    }

    function parseSide($side) {
        $list = [];
        foreach ($side as $item) {
            $item = trim($item);
            array_push($list, $this->parseMolecule($item));
        }
        return $list;
    }
    function parseEquation($string) {
        $sides = explode("=", $string);
        $left = explode("+",$sides[0]);
        $right = explode("+",$sides[1]);
        $reactants = $this->parseSide($left);
        $products = $this->parseSide($right);
        return new Equation($reactants, $products);
    }

    function parseMolecule($formula) {
        global $ELEMENTS, $APPENDIX;

        // Get num of moles
        preg_match("/(^\d*)\s?(.+)/", $formula, $matches);
        if ($matches[1]) {
            $formula = $matches[2];
            $num = (int)$matches[1];
        } else {
            $num = 1;
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
        if (!array_key_exists($formula, $APPENDIX)) {
            foreach (array_keys($APPENDIX) as $m) {
                if (startsWith($m, $formula)) {
                    $formula = $m;
                    break;
                }
            }
        }
        if (array_key_exists($formula, $APPENDIX)) {
            $appendices = $APPENDIX[$formula];
        } else {
            $appendices = null;
        }

        // Get appendix properties for phase, if specified

        if ($appendices) {
            if ($phase != null) {
                foreach ($appendices as $key=>$val) {
                    if ($key == $phase) {
                        $phase = $key;
                        break;
                    }
                }
                if (!$phase) {
                    foreach ($appendices as $key=>$val) {
                        if (startsWith($key, substr($phase, 0, -1))) {
                            $phase = $key;
                            break;
                        }
                    }
                }
            }
            if ($phase == null) {
                $phase = key($appendices);
            }
        }

        // Get charge of molecule, if specified

        $charge = 0;
        if (endsWith($formula, "-")) {
            $charge = -1;
        } else if (endsWith($formula, "+")) {
            $charge = 1;
        } else {
            $pos = strpos($formula, "-");
            if ($pos) {
                $charge = (int)substr($formula, $pos);
                $formula = substr($formula, 0, $pos);
            } else {
                $pos = strpos($formula, "+");
                if ($pos) {
                    $charge = (int)substr($formula, $pos);
                    $formula = substr($formula, 0, $pos);
                }
            }
        }

        // Divide molecule into constituent atoms

        $constituents = [];
        $pattern = join("|",array_keys($ELEMENTS));
        preg_match_all("/(?:$pattern)\d*|\((?:(?:$pattern)\d*)+\)\d*/", $formula, $matches);
        foreach ($matches[0] as $match) {
            array_push($constituents, $this->parseConstituent($match));
        }

        return new Molecule($num, $constituents, $charge, $phase, $appendices);
    }

    function parseConstituent($string) {
        global $ELEMENTS;

        $particles = [];
        $pattern = join("|",array_keys($ELEMENTS));

        if (startsWith($string, "(")) {  // it is polyatomic (i.e. (NO3)2 or (OH)2)
            preg_match("/^(.+?)(\d*)$/", $string, $match);
            $num = ($match[2] == "") ? 1 : (int)$match[2];
            $poly = substr($match[1], 1, -1);

            preg_match_all("/(?:$pattern)\d*/", $poly, $matches);
            foreach ($matches[0] as $match) {
                array_push($particles, $this->parseConstituent($match));
            }
        } else {  // it is a single element (i.e. C or N2)
            preg_match("/^(.+?)(\d*)$/", $string, $match);
            $num = ($match[2] == "") ? 1 : (int)$match[2];
            $sym = $match[1];
            $atom = new Atom($sym);
            array_push($particles, $atom);
        }
        return new Constituent($num, $particles);
    }
}
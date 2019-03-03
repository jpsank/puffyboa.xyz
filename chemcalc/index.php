<?php

function startsWith($haystack, $needle) {
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}
function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}

function readJSON($file) {
    $string = file_get_contents($file);
    $json_a = json_decode($string, true);
    return $json_a;
}

$ELEMENTS = readJSON("elements.json");
$APPENDIX = readJSON("appendix.json");


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

}

class Constituent {
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
                array_push($this->particles, new Constituent($match));
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

}

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

        $this->phase = null;
        $pattern = "/\s?(\((g|l|s|aq|a|c).*\))$/";
        preg_match($pattern, $formula, $matches, PREG_OFFSET_CAPTURE);
        if ($matches) {
            $this->phase = $matches[1][0];
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
            if ($this->phase) {
                foreach ($this->appendices as $key=>$val) {
                    if ($key == $this->phase) {
                        $this->phase = $key;
                        break;
                    }
                }
                if (!$this->phase) {
                    foreach ($this->appendices as $key=>$val) {
                        if (startsWith($key, substr($this->phase, 0, -1))) {
                            $this->phase = $key;
                            break;
                        }
                    }
                }
            }
            if (!$this->phase) {
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
            array_push($this->constituents, new Constituent($match));
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
        $formula = $this->getFormula();
        $phase = $this->getCurrentPhase();
        $num = ($this->num!=1)? $this->num : "";
        $string = $num . $formula . " " . $phase;

        if ($format) {
            $encoded = urlencode($this->getFlatFormula() . " " . $phase);
            $string = "<a class='formula' href='?input=$encoded'>$string</a>";
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
        return "(N/A)";
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

}


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
            foreach ($mol->constituents as $atom) {
                $sym = $atom->elem["symbol"];
                if (!$dict[$sym]) {
                    $dict[$sym] = 0;
                }
                $dict[$sym] += $mol->num * $atom->num;
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

function prettyPrint($dict) {
    $lengths = array_map('strlen', array_keys($dict));
    $max = max($lengths);
    foreach ($dict as $key => $val) {
        $key = str_pad($key, $max);
        echo "<div><pre class='key'>$key </pre><pre class='val'>$val</pre></div>";
    }
}

?>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../chemcalc/style.css">
    <title>ChemCalc - puffyboa.xyz</title>
    <link rel="shortcut icon" href="../assets/img/favicon.png" />
</head>

<body>

<section id="jumbo">
    <h1>ChemCalc</h1>
    <p>Calculate the properties for all your chemical equations</p>
    <p>i.e.
    <?php
    $examples = ["NaOH + HCl = NaCl + H2O","H2O","3Fe2O3 + CO = CO2 + 2Fe3O4","CH3OH", "H2O (g) = H2O (l)"];

    $input = $_GET['input'];
    $output = [];
    foreach ($examples as $example) {
        $encoded = urlencode($example);
        if ($input == $example) {
            array_push($output,"<a class='selected' href='?input=$encoded'>$example</a>");
        } else {
            array_push($output,"<a href='?input=$encoded'>$example</a>");
        }
    }
    echo join(", ",$output);
    ?>
    </p>
</section>

<section id="main">

    <form id="form" method="get">
        <input type="text" name="input" autocomplete="off" spellcheck="false"
               placeholder="chemical equation or molecule" maxlength="1000"
               value="<?php echo str_replace("%2B", "+", $_GET['input']); ?>">
        <input type="submit">
    </form>

    <div id="answer">
        <?php

        // Did the user submit input
        if (isset($_GET['input'])) {
            if ($_GET['input'] != '') {
                $input = $_GET['input'];
                $input = str_replace("%2B", "+", $input);
                if ((strpos($input, '=') !== false)) {
                    $equation = new Equation($input);
                    $dict = [];
                    $dict["Reaction"] = $equation->getBreakdown();
                    $dict["Is balanced"] = $equation->isBalanced()? "Yes": "No";
                    if ($equation->checkHasSupport()) {
                        $dict["Enthalpy of rxn"] = $equation->getEnthalpy()
                            . " kJ/mol (" . $equation->getEnthalpyBehavior() . ")";
                        $dict["Entropy of rxn"] = $equation->getEntropy()
                            . " J/molK (" . $equation->getEntropyBehavior() . ")";
                        $dict["Gibbs free energy of rxn"] = $equation->getGibbs()
                            . " kJ/mol (" . $equation->getGibbsBehavior() . ")";
                        $dict["Behavior"] = $equation->getBehavior();
                    }
                    prettyPrint($dict);
                } else {
                    $molecule = new Molecule($input);
                    $dict = [];
                    $dict["Composition"] = $molecule->getComposition();
                    $dict["Molar mass"] = $molecule->getMass() . " g/mol";
                    if ($molecule->appendices) {
                        $dict["Available phases"] = $molecule->getPhasesHTML();
                        $dict["Enthalpy of formation"] = $molecule->getEnthalpy() . " kJ/mol";
                        $dict["Entropy of formation"] = $molecule->getEntropy() . " J/molK";
                        $dict["Gibbs free energy of formation"] = $molecule->getGibbs() . " kJ/mol";
                    }
                    echo "<div><pre>" . $molecule->getFormulaHTML() . "</pre></div>";
                    prettyPrint($dict);
                }
            }
        }

        ?>
    </div>

</section>

</body>
</html>


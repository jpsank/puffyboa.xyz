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
    public $num;

    function __construct($sym, $num) {
        global $ELEMENTS;
        $this->elem = $ELEMENTS[$sym];
        $this->num = $num;
    }

}

class Molecule {
    public $charge;
    public $appendix;
    public $constituents;
    public $num;

    function __construct($formula) {
        global $ELEMENTS, $APPENDIX;

        // Get num of moles
        preg_match("/(^\d*)(.+)/", $formula, $matches);
        if ($matches[1]) {
            $formula = substr($formula,sizeof($matches[1]));
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

        if (!in_array($formula, array_keys($APPENDIX))) {
            foreach (array_keys($APPENDIX) as $m) {
                if (startsWith($m, $formula)) {
                    $formula = $m;
                    break;
                }
            }
        }

        // Get appendix properties for phase, if specified

        if ($phase) {
            foreach ($APPENDIX[$formula] as $state) {
                if ($state["phase"] == $phase) {
                    $this->appendix = $state;
                    break;
                }
            }
            if ($this->appendix == null) {
                foreach ($APPENDIX[$formula] as $state) {
                    if (startsWith($state["phase"],substr($phase,0,-1))) {
                        $this->appendix = $state;
                        break;
                    }
                }
            }
        }
        if ($this->appendix == null) {
            $this->appendix = $APPENDIX[$formula][0];
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
        $pattern = "/($pattern)(\d*)/";
        preg_match_all($pattern, $formula, $matches);
        for ($i = 0; $i < sizeof($matches[0]); $i++) {
            $sym = $matches[1][$i];
            $num = ($matches[2][$i]=="")? 1: (int)$matches[2][$i];
            $atom = new Atom($sym, $num);
            array_push($this->constituents, $atom);
        }
	}

    function getFormula() {
        $string = "";
        foreach ($this->constituents as $atom) {
            $num = ($atom->num==1)? "" : "<sub>$atom->num</sub>";
            $string .= $atom->elem['symbol'] . $num;
        }
        if ($this->charge > 0) {
            $string .= "<sup>+$this->charge</sup>";
        } else if ($this->charge < 0) {
            $string .= "<sup>$this->charge</sup>";
        }
        return $string;
    }

    function getEnthalpy() {
        if ($this->appendix) {
            return $this->appendix["enthalpy"];
        }
        return false;
    }
    function getEntropy() {
        if ($this->appendix) {
            return $this->appendix["entropy"];
        }
        return false;
    }
    function getGibbs() {
        if ($this->appendix) {
            return $this->appendix["gibbs"];
        }
        return false;
    }
    function getPhase() {
        if ($this->appendix) {
            return $this->appendix["phase"];
        }
        return "(N/A)";
    }

    function getMass() {
        $mm = 0;
        foreach ($this->constituents as $atom) {
            $mm += $atom->elem["atomic_mass"]*$atom->num;
        }
        return $mm;
    }

    function getComposition() {
        $props = [];
        foreach ($this->constituents as $atom) {
            $name = $atom->elem['name'];
            $atomic_mass = $atom->elem['atomic_mass'];
            array_push($props, "($name $atomic_mass g x $atom->num)");
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

    function getBreakdown() {
        $left = [];
        foreach ($this->reactants as $mol) {
            $num = $mol->num != 1? $mol->num : "";
            array_push($left,$num . $mol->getFormula() . " " . $mol->getPhase());
        }
        $left = join(" + ", $left);
        $right = [];
        foreach ($this->products as $mol) {
            $num = $mol->num != 1? $mol->num : "";
            array_push($right,$num . $mol->getFormula() . " " . $mol->getPhase());
        }
        $right = join(" + ", $right);
        return "$left --> $right";
    }

    function checkHasSupport() {
        foreach (array_merge($this->reactants,$this->products) as $mol) {
            if (!$mol->appendix) {
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
        if ($enthalpy == false) {
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
        echo "<div><pre class='key'>$key : </pre><pre class='val'>$val</pre></div>";
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
        $examples = ["NaOH + HCl = NaCl + H2O","H2O","3Fe2O3 + CO = CO2 + 2Fe3O4","C2H6"];

        $output = [];
        foreach ($examples as $example) {
            $encoded = urlencode($example);
            array_push($output,"<a href='?input=$encoded'>$example</a>");
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
                    if ($molecule->appendix) {
                        $dict["Enthalpy of formation"] = $molecule->getEnthalpy() . " kJ/mol";
                        $dict["Entropy of formation"] = $molecule->getEntropy() . " J/molK";
                        $dict["Gibbs free energy of formation"] = $molecule->getGibbs() . " kJ/mol";
                    }
                    echo "<div><pre>" . $molecule->getFormula() . "</pre></div>";
                    prettyPrint($dict);
                }
            }
        }

        ?>
    </div>

</section>

</body>
</html>


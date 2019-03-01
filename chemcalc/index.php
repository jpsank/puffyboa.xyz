<?php

function startsWith ($string, $startString)
{
    $len = strlen($startString);
    return (substr($string, 0, $len) === $startString);
}

function readJSON($file) {
    $string = file_get_contents($file);
    $json_a = json_decode($string, true);
    return $json_a;
}

$ELEMENTS = readJSON("elements.json");
$APPENDIX = readJSON("appendix.json");

class Molecule {
    public $formula;
    public $appendices;
    public $constituents;

    function __construct($formula) {
        global $ELEMENTS, $APPENDIX;

        $this->formula = $formula;

        $this->constituents = [];
        $pattern = "/" . join("\d*|",array_keys($ELEMENTS)) . "/";
        preg_match_all($pattern, $formula, $matches);
        foreach ($matches[0] as $atom) {
            preg_match("/\d/", $atom, $dm, PREG_OFFSET_CAPTURE);
            if ($dm) {
                $i = $dm[1];
                $sym = substr($atom,0,$i-1);
                $digit = substr($atom, $i-1);
            } else {
                $sym = $atom;
                $digit = 1;
            }
            $elem = $ELEMENTS[$sym];
            array_push($this->constituents, [$elem, $digit]);
        }

        if (!in_array($this->formula, array_keys($APPENDIX))) {
            foreach (array_keys($APPENDIX) as $m) {
                if (startsWith($m, $this->formula)) {
                    $this->formula = $m;
                    $this->setAppendix($this->formula);
                    break;
                }
            }
        } else {
            $this->setAppendix($this->formula);
        }
	}

    function setAppendix($formula) {
        global $APPENDIX;
        $this->appendix = $APPENDIX[$formula][0];
    }

    function getEnthalpy() {
        return $this->appendix["enthalpy"];
    }
    function getEntropy() {
        return $this->appendix["entropy"];
    }
    function getGibbs() {
        return $this->appendix["gibbs"];
    }
    function getPhase() {
        return $this->appendix["phase"];
    }

    function getMass() {
        $mm = 0;
        foreach ($this->constituents as $item) {
            $elem = $item[0];
            $digit = $item[1];
            $mm += $elem["atomic_mass"]*$digit;
        }
    }

    function breakdown() {
        $props = [];
        foreach ($this->constituents as $item) {
            $elem = $item[0];
            $digit = $item[1];
            $name = $elem['name'];
            $atomic_mass = $elem['atomic_mass'];
            array_push($props, "($name $atomic_mass g x $digit)");
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
            preg_match("/(^\d*)(.+)/", $item, $matches);
            if ($matches[1]) {
                $num = (int)$matches[1];
            } else {
                $num = 1;
            }
            $mol = new Molecule($matches[2]);
            array_push($list, [$num, $mol]);
        }
        return $list;
    }
    function parseEqStr($string) {
        $sides = explode("=", $string);
        $left = explode("+",$sides[0]);
        $right = explode("+",$sides[1]);
        $reactants = $this->parseSide($left);
        $products = $this->parseSide($right);
        return [$reactants,$products];
    }

    function getBreakdown() {
        $rString = [];
        foreach ($this->reactants as $r) {
            $num = $r[0];
            $mol = $r[1];
            array_push($rString,$num . "x[" . $mol->formula . "]" . $mol->getPhase());
        }
        $rString = join(" + ", $rString);

        $pString = [];
        foreach ($this->products as $p) {
            $num = $p[0];
            $mol = $p[1];
            array_push($pString,$num . "x[" . $mol->formula . "]" . $mol->getPhase());
        }
        $pString = join(" + ", $pString);

        return "$rString --> $pString";
    }

    function getEnthalpy() {
        $num_r = 0;
        foreach ($this->reactants as $item) {
            $num_r += $item[0]*$item[1]->getEnthalpy();
        }
        $num_p = 0;
        foreach ($this->products as $item) {
            $num_p += $item[0]*$item[1]->getEnthalpy();
        }
        return $num_p-$num_r;
    }
    function getEntropy() {
        $num_r = 0;
        foreach ($this->reactants as $item) {
            $num_r += $item[0]*$item[1]->getEntropy();
        }
        $num_p = 0;
        foreach ($this->products as $item) {
            $num_p += $item[0]*$item[1]->getEntropy();
        }
        return $num_p-$num_r;
    }
    function getGibbs() {
        $num_r = 0;
        foreach ($this->reactants as $item) {
            $num_r += $item[0]*$item[1]->getGibbs();
        }
        $num_p = 0;
        foreach ($this->products as $item) {
            $num_p += $item[0]*$item[1]->getGibbs();
        }
        return $num_p-$num_r;
    }

}

function prettyPrint($dict) {
    $lengths = array_map('strlen', array_keys($dict));
    $max = max($lengths);
    foreach ($dict as $key => $val) {
        $key = str_pad($key, $max);
        echo "<pre>$key: $val</pre>";
    }
}

?>

<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../chemcalc/style.css">
    <title>ChemCalc - puffyboa.xyz</title>
    <link rel="shortcut icon" href="../assets/img/favicon.png" />
</head>

<body>

<section id="jumbo">
    <h1>ChemCalc</h1>
    <p>Calculate the properties for all your chemical equations</p>
</section>

<section id="main">

    <form id="form" method="post">
        <input type="text" name="input" autocomplete="off" spellcheck="false" placeholder="chemical equation or molecule" maxlength="1000">
        <input type="submit">
    </form>

    <div id="answer">
        <?php

        // Did the user submit input
        if (isset($_POST['input'])) {
            if ($_POST['input'] != '') {
                $input = $_POST['input'];
                if ((strpos($input, '=') !== false)) {
                    $equation = new Equation($input);
                    $dict = [];
                    $dict["Breakdown"] = $equation->getBreakdown();
                    $dict["Enthalpy of rxn"] = $equation->getEnthalpy();
                    $dict["Entropy of rxn"] = $equation->getEntropy();
                    $dict["Gibbs free energy of rxn"] = $equation->getGibbs();
                    echo "<pre>$input</pre>";
                    prettyPrint($dict);
                } else {
                    $molecule = new Molecule($input);
                    $dict = [];
                    $dict["Breakdown"] = $molecule->breakdown();
                    $dict["Enthalpy of formation"] = $molecule->getEnthalpy() . " kJ/mol";
                    $dict["Entropy of formation"] = $molecule->getEntropy() . " J/molK";
                    $dict["Gibbs free energy of formation"] = $molecule->getGibbs() . " kJ/mol";
                    echo "<pre>$molecule->formula</pre>";
                    prettyPrint($dict);
                }
            }
        }

        ?>
    </div>

</section>

</body>
</html>


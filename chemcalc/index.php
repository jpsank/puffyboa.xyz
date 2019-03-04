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

require("Constituent.php");
require("Molecule.php");
require("Equation.php");


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

    if (isset($_GET['input'])) {
        $input = $_GET['input'];
    } else {
        $input = null;
    }
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
               placeholder="chemical equation or molecule" maxlength="1000" required="required"
               value="<?php
               if (isset($_GET['input'])) {
                   echo str_replace("%2B", "+", $_GET['input']);
               }
               ?>">
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
                        $dict["Favorable at 25 Celsius"] = $equation->isFavorableAt(298.15)? "Yes": "No";
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


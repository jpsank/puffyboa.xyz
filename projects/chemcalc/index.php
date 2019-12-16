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

require_once "Parser.php";
require_once "Constituent.php";
require_once "Molecule.php";
require_once "Equation.php";


function html_entity_strlen($html) {
    return strlen(utf8_decode(html_entity_decode($html, ENT_COMPAT, 'utf-8')));
}

function prettyPrint($dict) {
    foreach ($dict as $key => $val) {
        $color = '';
        if ($val == 'No') { $color = " red"; }
        else if ($val == 'Yes') { $color = " green"; }
        echo "<tr><td class='key'>$key </td><td class='val$color'>$val</td></tr>";
    }
}

?>

<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="description" content="Calculate the properties for all your chemical equations and molecules">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" type="text/css" href="../../assets/css/shared.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>ChemCalc - puffyboa.xyz</title>
    <link rel="shortcut icon" href="../../assets/img/favicon.png" />
</head>

<body>

<div class="back-to-home">
    <a href="../../index.html">puffyboa.xyz</a>
    <a href="index.php">ChemCalc</a>
</div>

<section id="jumbo">
    <h1>ChemCalc</h1>
    <a href="table.html">See Periodic Table</a>
    <p>Calculate the properties for all your chemical equations and molecules</p>
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
                $parser = new Parser();
                if ((strpos($input, '=') !== false)) {
                    $equation = $parser->parseEquation($input);

                    $isBalanced = $equation->isBalanced();

                    $dict = [];

                    if (!$isBalanced) {
                        $newEq = $equation->getBalancedEq();
                        if ($newEq) {
                            $flat = urlencode($newEq->getEquationStr(false,false));
                            echo "<tr><td><a class='blue' href='?input=$flat'>Go to balanced version</a></td></tr>";
                        } else {
                            echo "<tr><td class='red'>Unable to calculate balanced equation</td></tr>";
                        }
                    }

                    $dict["Reaction"] = $equation->getEquationStr(true,true);
                    $dict["Is balanced"] = $isBalanced? "Yes": "No";
                    if ($equation->checkHasSupport()) {
                        $dict["&Delta;H&deg;<sub>rxn</sub>"] = $equation->getEnthalpy()
                            . " kJ/mol (" . $equation->getEnthalpyBehavior() . ")";
                        $dict["&Delta;S&deg;<sub>rxn</sub>"] = $equation->getEntropy()
                            . " J/molK (" . $equation->getEntropyBehavior() . ")";
                        $dict["&Delta;G&deg;<sub>rxn</sub>"] = $equation->getGibbs()
                            . " kJ/mol (" . $equation->getGibbsBehavior() . ")";
                        $dict["Behavior"] = $equation->getBehavior();
                        $dict["Favorable at 25 Celsius"] = $equation->isFavorableAt(298.15)? "Yes": "No";
                    }
                    echo "<table class='props'>";
                    prettyPrint($dict);
                    echo "</table>";
                } else {
                    $molecule = $parser->parseMolecule($input);
                    $dict = [];
                    $dict["Composition"] = $molecule->getComposition();
                    $dict["Average molar mass"] = $molecule->getMass() . " g/mol";
                    if ($molecule->appendices) {
                        $dict["Available phases"] = $molecule->getPhasesHTML();
                        $dict["&Delta;H&deg;<sub>f</sub>"] = $molecule->getEnthalpy() . " kJ/mol";
                        $dict["&Delta;S&deg;<sub>f</sub>"] = $molecule->getEntropy() . " J/molK";
                        $dict["&Delta;G&deg;<sub>f</sub>"] = $molecule->getGibbs() . " kJ/mol";
                    }
                    echo "<table class='props'>";
                    echo "<tr><td>" . $molecule->getFullFormula() . "</td></tr>";
                    prettyPrint($dict);
                    echo "</table>";

                    $elem = $molecule->isSingleElement();
                    if ($elem !== false) {
                        $dict2 = [];
                        $dict2["Appearance"] = $elem["appearance"];
                        $dict2["Molar Mass"] = $elem["atomic_mass"]? $elem["atomic_mass"] . " g/mol" : "";
                        $dict2["Boiling point"] = $elem["boil"]? $elem["boil"] . " K" : "";
                        $dict2["Category"] = $elem["category"];
                        $dict2["Color"] = $elem["color"];
                        $dict2["Density"] = $elem["density"]? $elem["density"] . " g/cm3" : "";
                        $dict2["Discovered by"] = $elem["discovered_by"];
                        $dict2["Melting point"] = $elem["melt"] ? $elem["melt"] . " K" : "";
                        $dict2["Molar heat"] = $elem["molar_heat"]? $elem["molar_heat"] . " J/molK" : "";
                        $dict2["Named by"] = $elem["named_by"];
                        $dict2["Atomic Number"] = $elem["number"];
                        $dict2["Phase"] = $elem["phase"];
                        $dict2["Source"] = $elem["source"];
                        $dict2["Spectral img"] = $elem["spectral_img"];
                        $dict2["Shells"] = json_encode($elem["shells"]);

                        $name = $elem["name"];
                        $summary = $elem["summary"];

                        echo "<table class='atomic_props'>";
                        echo "<h2>Atomic Properties of $name</h2>";
                        echo "<p>$summary</p>";
                        prettyPrint($dict2);
                        echo "</table>";
                    }
                }
            }
        }

        ?>
    </div>

</section>

</body>
</html>


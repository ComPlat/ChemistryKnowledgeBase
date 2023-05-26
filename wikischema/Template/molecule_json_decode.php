<?php
$jsonobj = '{
    "@context" :{
        "OLS":"http://www.ebi.ac.uk/ols/api/ontologies/",
        "CID":"OLS:CHEMINF/terms/http://www.ebi.ac.uk/ols/api/ontologies/CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000140",
        "CAS":"OLS:CHEMINF/terms/http%3A%2F%2Fsemanticscience.org%2Fresource%2FCHEMINF_000446",
        "Iupac_Name":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000107",
        "Trivial_Name":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000109",
        "Exact_Mass":"OLS:AFO/terms/http%253A%252F%252Fpurl.allotrope.org%252Fontologies%252Fquality%2523AFQ_0000123",
        "Molecular_formula":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000042",
        "LogP":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000251",
        "SMILES":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000018",
        "Inchi_key":"OLS:CHEMINF/terms/http%253A%252F%252Fsemanticscience.org%252Fresource%252FCHEMINF_000059"
    },
    "@type":"Molecule",
    "@id":"",
    "CID":"",
    "CAS":"",
    "Iupac_Name":"",
    "Abbreviation":"",
    "Trivial_Name":"",
    "Exact_Mass":"",
    "Molecular_formula":"",
    "LogP":"",
    "Has_Vendors":"",
    "Synoynms":"",
    "SMILES":"",
    "Inchi":"",
    "Inchi_key":""
}';

$json_decoded = (json_decode($jsonobj, true));
// var_dump(array_keys($json_decoded));
// var_dump($json_decoded["@id"]);
$json_decoded["CID"]= 5;
var_dump($json_decoded["CID"]);
$json_rencoded = (json_encode($json_decoded));
echo $json_rencoded;
?>

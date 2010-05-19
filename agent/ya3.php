<?php
include_once("../../arc/ARC2.php");

$DEBUG = false;

$defaultprefixes = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> PREFIX dcterms: <http://purl.org/dc/terms/> PREFIX foaf: <http://xmlns.com/foaf/0.1/> PREFIX ea: <http://purl.org/NET/entity-actions#> ";

$defaultEntityActionSetURI = "http://localhost:8888/alodin/aset";
$defaultEntityActionSetDocURI = "http://localhost:8888/alodin/aset/as.ttl";

/* ARC2 RDF store config - START */
$config = array(
	'db_name' => 'arc2',
	'db_user' => 'root',
	'db_pwd' => 'root',
	'store_name' => 'ya3'
); 

$store = ARC2::getStore($config);

if (!$store->isSetUp()) {
  $store->setUp();
  echo 'set up';
}
/* ARC2 RDF store config - END */

// Ya3 HTTP interface
if(isset($_GET['reset'])) {
	$store->reset();
	echo "RESET store done.<br />\n";
	loadEntity($defaultEntityActionSetURI, $defaultEntityActionSetDocURI);
	echo "<p>go <a href='index.html'>home</a> ...</p>\n";     
}

if(isset($_GET['entity'])) { 
	$entityURI = $_GET['entity'];
	echo ask4Actions($entityURI);
}

if(isset($_GET['check'])) { 
	$entityURI = $_GET['check'];
	$attributeURI = $_GET['attribute'];
	echo getAttributeValueCount($entityURI, $attributeURI);
}


if(isset($_GET['action'])) { 
	$actionURI = $_GET['action'];
	$entityURI = $_GET['onEntity'];
	$entityConceptURI = $_GET['onEntityConcept'];
	
	echo executeAction($actionURI, $entityURI, $entityConceptURI);
}

if(isset($_GET['list'])) { 
	echo listEntityActions();
}



// Ya3 fucntions
function ask4Actions($entityURI){
	global $store;
	global $defaultprefixes;
	global $DEBUG;

	$entityConceptList = array();

	if(!isDataLocal($entityURI)) { // we haven't tried to dereference the entity URI yet
		$entityDocURI = isDereferenceableURI($entityURI);
		if(!$entityDocURI) {
			return "Unable to retrieve RDF/XML from URI <a href='$entityURI'>$entityURI</a>.";
		}
		else {
			loadEntity($entityURI, $entityDocURI); 
		}
	}
	
	$cmd = $defaultprefixes;
	$cmd .= "SELECT *  FROM <" . $entityURI . "> WHERE "; 
	$cmd .= "{ <$entityURI> a ?type . }";
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";
	
	$results = $store->query($cmd);
	
	if($results['result']['rows']) {
		foreach ($results['result']['rows'] as $entity) {
			array_push($entityConceptList, $entity['type']);
		}
	}
	
	// check if there are actions known for the entity's concepts
	$retval .= ask4ActionsOnEntities($entityURI, $entityConceptList);
	
	return $retval;
}

function ask4ActionsOnEntities($entityURI, $entityConceptList){
	global $DEBUG;

	$retval = "<p>The following actions are available for the entity:</p><div>";
	
	// the view action, always available
	$retval = "<div class='actioncontainer'><div class='actiondisplay'><img src='../aset/action-symbols/view-action.png' alt='view' title='view' /></div><div class='action' resource='view' typeof='none'>view</div></div>";
	
	foreach($entityConceptList as $entityConcept) {
		$retval .=  " " . lookupActions4EntityWithConcept($entityConcept);
	}
	$retval .= "</div>";
	return $retval;
}

function lookupActions4EntityWithConcept($entityConcept){
	global $store;
	global $defaultprefixes;
	global $defaultEntityActionSetURI;
	global $DEBUG;

	$cmd = $defaultprefixes;
	$cmd .= "SELECT *  FROM <" . $defaultEntityActionSetURI . "> WHERE "; 
	$cmd .= "{ ?entity a ea:Entity ;  ea:match ?match . ?match ea:concept <$entityConcept> ; ea:action ?action .  ?action dcterms:title ?title . OPTIONAL { ?action dcterms:description ?description } . OPTIONAL { ?action foaf:depiction ?symbol } }";
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";
	
	$results = $store->query($cmd);	
	
	if($results['result']['rows']) {
		foreach ($results['result']['rows'] as $row) {
			$display = "";
			$description = "";
			
			if($row['description']) { 
				$description = $row['description']; 
			}
			else  {
				$description = "no detailed description of the action available, sorry ...";
			}
			
			if($row['symbol']) { 
				$display = "<img src='" . $row['symbol'] . "' alt='$description' title='$description' />"; }
			else {
				$display = "<img src='../aset/action-symbols/default-action.png' alt='$description' title='$description' />";
			}

			
			$retval .= "<div class='actioncontainer'><div class='actiondisplay'>$display</div><div class='action' resource='" . $row['action'] . "' typeof='" . $entityConcept . "'>" . $row['title']. "</div></div>";
		}
	}
	
	return $retval;
}

function executeAction($actionURI, $entityURI, $entityConceptURI) {
	global $store;
	global $defaultprefixes;
	global $defaultEntityActionSetURI;
	global $DEBUG;


	if($actionURI === "view"){
		return "http://sig.ma/search?singlesource=" . $entityURI . "&raw=1";
	}
	else {
		$cmd = $defaultprefixes;
		$cmd .= "SELECT *  FROM <" . $defaultEntityActionSetURI . "> WHERE "; 
		$cmd .= "{ <$actionURI> ea:using ?serviceURI . ?entity ea:match ?match . ?match ea:concept <$entityConceptURI> ; ea:action <$actionURI> ;  ea:attribute ?prop . }";

		if($DEBUG) echo htmlentities($cmd) . "<br />";

		$results = $store->query($cmd);

		if($results['result']['rows']) {
			foreach ($results['result']['rows'] as $row) {
				$serviceURI = $row['serviceURI'] . getAttributeValue($entityURI, $row['prop']);
				return $serviceURI;
			}
		}
	}

}

function getAttributeValueCount($entityURI, $attributeURI){
	global $store;
	global $defaultprefixes;
	global $DEBUG;

	$cmd = $defaultprefixes;
	$cmd .= "SELECT *  FROM <" . $entityURI . "> WHERE "; 
	$cmd .= "{ <$entityURI> <$attributeURI> ?val . }";
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";
	
	$results = $store->query($cmd);
	
	return count($results['result']['rows']);
	
}


function getAttributeValue($entityURI, $attributeURI){
	global $store;
	global $defaultprefixes;
	global $DEBUG;

	$cmd = $defaultprefixes;
	$cmd .= "SELECT *  FROM <" . $entityURI . "> WHERE "; 
	$cmd .= "{ <$entityURI> <$attributeURI> ?val . }";
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";
	
	$results = $store->query($cmd);
	
	if($results['result']['rows']) {
		foreach ($results['result']['rows'] as $row) {
			 return $row['val'];  
		}
	}
}

function listEntityActions() {
	global $store;
	global $defaultprefixes;
	global $defaultEntityActionSetURI;
	global $DEBUG;
	
	$entityList = array();
	$processedActionList = array();
	

	$cmd = $defaultprefixes;
	$cmd .= "SELECT DISTINCT *  FROM <" . $defaultEntityActionSetURI . "> WHERE "; 
	$cmd .= "{ ?entity a ea:Entity ; dcterms:title ?title  . }";
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";
	
	$results = $store->query($cmd);
	
	if($results['result']['rows']) {
		foreach ($results['result']['rows'] as $row) {
			$entityList[$row['entity']] = $row['title'];
		}
	}

	//var_dump($entityList);
	asort($entityList);
	
	foreach($entityList as $entityURI => $entityLabel ) {
		$retval .= "<div>For <a href='" . $entityURI ."'>" . $entityLabel . "</a> the following actions are available:"; 

		$cmd = $defaultprefixes;
		$cmd .= "SELECT DISTINCT *  FROM <" . $defaultEntityActionSetURI . "> WHERE "; 
		$cmd .= "{ <$entityURI> a ea:Entity ; ea:match ?match . ?match ea:concept ?entityConcept ; ea:action ?action .  ?action dcterms:title ?atitle . OPTIONAL { ?action dcterms:description ?description } . } ORDER BY ?atitle";

		if($DEBUG) echo htmlentities($cmd) . "<br />";

		$results = $store->query($cmd);

		if($results['result']['rows']) {
			$retval .= "<ul>";
			foreach ($results['result']['rows'] as $row) {
				$description = "";
				if($row['description']) { 
					$description = $row['description']; 
				}
				else  {
					$description = "-";
				}
				if(in_array($row['action'], $processedActionList)) { // we've already processed this action
					
				}
				else { // first time to see this action; let's remember it
					$retval .= "<li> <a href='" . $row['action'] ."'>" . $row['atitle'] . "</a> (for type " . $row['entityConcept'] .")</li>"; 	
					array_push($processedActionList, $row['action'] );
				}
				
			}
			$retval .= "</ul>";
		}
		
		$retval .= "</div>"; 
	}
	
	

	
	
	return $retval;	
}

// Web data management functions
function isDataLocal($graphURI){
	global $store;
	
	$cmd = "SELECT ?s FROM <$graphURI> WHERE { ?s ?p ?o .}";

	$results = $store->query($cmd);
	
	if($results['result']['rows']) return true;
	else return false;
}

function loadEntity($entityURI, $entityDocURI) {
	global $store;
	global $DEBUG;
	
	$cmd .= "LOAD <$entityDocURI> INTO <$entityURI>"; 
	
	if($DEBUG) echo htmlentities($cmd) . "<br />";

	$store->query($cmd);
	$errs = $store->getErrors();
	
	return $errs;
	
}

function isDereferenceableURI($URI){
	$ret = false;
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_HEADER, 0);
	curl_setopt($c, CURLOPT_HTTPHEADER, array('Accept: application/rdf+xml')); 
	curl_setopt($c, CURLOPT_URL, $URI);
	curl_setopt($c,	CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($c, CURLOPT_TIMEOUT, 30);
	curl_exec($c);
	if(!curl_errno($c)) {
		$info = curl_getinfo($c);
		if($info['http_code'] == "200") $ret = $info['url'];
		else $ret = false;
	}
	else $ret = false;
	curl_close($c);
	return $ret;
}

?>
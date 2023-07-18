<?php 


include("_infra/functions.php");

$q = "";
$endpoint = 'https://digitaalerfgoed.poolparty.biz/PoolParty/sparql/nhaf';

// TOPCONCEPTEN
$sparql = 'PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
SELECT ?top ?label (count(?concept) as ?nr) WHERE {
  ?scheme skos:hasTopConcept ?top .
  ?top skos:prefLabel ?label .
  ?top skos:inScheme <https://digitaalerfgoed.poolparty.biz/nhaf/01617d03-bcd9-4720-9392-bf6bd92227b9> .
  ?top skos:narrower ?concept .
} group by ?top ?label';

$json = getSparqlResults($endpoint,$sparql);
$data = json_decode($json,true);

$tops = array();

foreach ($data['results']['bindings'] as $key => $value) {
	$tops[] = array(
		"topconcept" => $value['top']['value'],
		"label" => $value['label']['value'],
		"nr" => $value['nr']['value']
	);
}




if(isset($_GET['q'])){
	$q = $_GET['q'];


	// SEARCH RESULTS
	$sparql = 'PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
	SELECT ?concept ?label WHERE {  
	  
	  ?concept skos:prefLabel ?label ;
	            skos:inScheme <https://digitaalerfgoed.poolparty.biz/nhaf/01617d03-bcd9-4720-9392-bf6bd92227b9> .
	  
	  FILTER(REGEX(?label,"' . $_GET['q'] . '","i"))
	}';

	$json = getSparqlResults($endpoint,$sparql);
	$data = json_decode($json,true);

	$searchresults = array();

	foreach ($data['results']['bindings'] as $key => $value) {
		$searchresults[] = array(
			"concept" => $value['concept']['value'],
			"label" => $value['label']['value']
		);
	}

	$count = count($searchresults);
	$quarter = ceil($count/4);
	$column1 = array_slice($searchresults,0,$quarter);
	$column2 = array_slice($searchresults,$quarter,$quarter);
	$column3 = array_slice($searchresults,2*$quarter,$quarter);
	$column4 = array_slice($searchresults,3*$quarter);

}elseif(isset($_GET['concept'])){




	// BROADER
	$sparql = "PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
							SELECT * WHERE {  
							  <" . $_GET['concept'] . "> skos:broader+ ?broader .
							  ?broader skos:prefLabel ?label .
							}";

	$json = getSparqlResults($endpoint,$sparql);
	$data = json_decode($json,true);

	$broader = array();

	foreach ($data['results']['bindings'] as $key => $value) {
		$broader[] = array(
			"broader" => $value['broader']['value'],
			"label" => $value['label']['value']
		);
	}
	$broader = array_reverse($broader);

	// NARROWER
	$sparql = "PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
						SELECT ?n ?label (count(?nn) as ?nr) WHERE {  
						  <" . $_GET['concept'] . "> skos:narrower ?n .
						  ?n skos:prefLabel ?label .
						  optional{
						  	?n skos:narrower ?nn .
						  }
						} group by ?n ?label order by ?label";

	$json = getSparqlResults($endpoint,$sparql);
	$data = json_decode($json,true);

	$narrower = array();

	foreach ($data['results']['bindings'] as $key => $value) {
		$narrower[] = array(
			"narrower" => $value['n']['value'],
			"label" => $value['label']['value'],
			"nr" => $value['nr']['value']
		);
	}



	// CURRENT TERM

	$sparql = "PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
							SELECT * WHERE {
							  <" . $_GET['concept'] . "> skos:prefLabel ?pref .
							  optional{
							  	<" . $_GET['concept'] . "> skos:altLabel ?alt .
							  	BIND(LANG(?alt) AS ?altlang) .
							  }
							   optional{
							  	<" . $_GET['concept'] . "> skos:scopeNote ?note . 
    							BIND(LANG(?note) AS ?notelang) .
							  }
							   optional{
							  	<" . $_GET['concept'] . "> skos:closeMatch ?close .
							  }
							} LIMIT 100";
	$json = getSparqlResults($endpoint,$sparql);
	$data = json_decode($json,true);

	$scopenotes = array();
	$alts = array();
	$closematches = array();

	foreach ($data['results']['bindings'] as $key => $value) {
		$term = $value['pref']['value'];
		if(isset($value['note']['value'])){
			$scopenotes[$value['note']['value']] = array(
				"note" => $value['note']['value'],
				"notelang" => $value['notelang']['value']
			);
		}
		if(isset($value['alt']['value'])){
			$alts[$value['alt']['value']] = array(
				"alt" => $value['alt']['value'],
				"altlang" => $value['altlang']['value']
			);
		}
		if(isset($value['close']['value']) && !in_array($value['close']['value'],$closematches)){
			$closematches[] = $value['close']['value'];
		}
	}


	// RELATED
	$sparql = "PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
						SELECT ?related ?label (count(?narrower) as ?nr) WHERE {
						  <" . $_GET['concept'] . "> skos:related ?related .
						  ?related skos:prefLabel ?label .
						  optional{
						  	?related skos:narrower ?narrower .
						  }
						} group by ?related ?label order by ?label";

	$json = getSparqlResults($endpoint,$sparql);
	$data = json_decode($json,true);

	$related = array();

	foreach ($data['results']['bindings'] as $key => $value) {
		$related[] = array(
			"related" => $value['related']['value'],
			"label" => $value['label']['value'],
			"nr" => $value['nr']['value']
		);
	}


}





include("_parts/header.php");

?>


<div id="skosapp">

	<div class="container-fluid">

		<div class="row">

			<div class="col-md-4">
				<form action="/skosdeboer/" method="get"><input type="text" name="q" id="searchbox" value="<?= $q ?>" /></form>
			</div>

			<div class="col-md-8" id="topconcepten">
				<?php foreach($tops as $row){ ?>
					🏔️ <strong><a href="?concept=<?= $row['topconcept'] ?>"><?= $row['label'] ?></a></strong>
					<span class="evensmaller">[<?= $row['nr'] ?>]</span>
				<?php } ?>
			</div>
		</div>
		


		<?php if(isset($_GET['q'])){ ?>

			<div id="search-results" class="row">

				<div class="col">
					<?php foreach($column1 as $row){ ?>
						<a href="?concept=<?= $row['concept'] ?>"><?= $row['label'] ?></a><br />
					<?php } ?>
				</div>
				<div class="col">
					<?php foreach($column2 as $row){ ?>
						<a href="?concept=<?= $row['concept'] ?>"><?= $row['label'] ?></a><br />
					<?php } ?>
				</div>
				<div class="col">
					<?php foreach($column3 as $row){ ?>
						<a href="?concept=<?= $row['concept'] ?>"><?= $row['label'] ?></a><br />
					<?php } ?>
				</div>
				<div class="col">
					<?php foreach($column4 as $row){ ?>
						<a href="?concept=<?= $row['concept'] ?>"><?= $row['label'] ?></a><br />
					<?php } ?>
				</div>

			</div>

		<?php }else{ ?>

			<div class="row">
				<div class="col-md-12">
					<?php foreach($broader as $row){ ?>
						<strong><a href="?concept=<?= $row['broader'] ?>"><?= $row['label'] ?></a></strong> ➡️ 
					<?php } ?>
				</div>
			</div>


			<div class="row" id="term">
				<div class="col-md-6">
					<h1><?= $term ?></h1>

					<a target="_blank" style="background-color: #ca2c25; display: inline-block; color: #fff; padding: 6px 20px;" href="https://noord-hollandsarchief.nl/beelden/beeldbankdeboer/?mode=gallery&view=horizontal&rows=1&page=1&fq%5B%5D=search_s_catalog_card:%22<?= $term ?>%22">bekijk in beeldbank</a>

					
				</div>
				<div class="col-md-3">
					<em>scope notes:</em><br />
					<?php foreach($scopenotes as $note){ ?>
						<?php if($note['notelang'] == "en"){ ?>
							🇬🇧
						<?php }else{ ?>
							🇳🇱
						<?php } ?>
						<?= $note['note'] ?><br />
					<?php } ?><br />

					<em>alt labels:</em><br />
					<?php foreach($alts as $alt){ ?>
						<?php if($alt['altlang'] == "en"){ ?>
							🇬🇧
						<?php }else{ ?>
							🇳🇱
						<?php } ?>
						<?= $alt['alt'] ?><br />
					<?php } ?><br />

					<em>AAT:</em><br />
					<?php foreach($closematches as $match){ ?>
						🔗 <a target="_blank" href="<?= $match ?>"><?= $match ?></a><br />
					<?php } ?>
				</div>
				<div class="col-md-3">
					<em>gerelateerde termen:</em><br />
					<?php foreach($related as $row){ ?>
						👩‍❤️‍👩 <a href="?concept=<?= $row['related'] ?>"><?= $row['label'] ?></a> 
						<?php if($row['nr']>0){ ?>
							<span class="evensmaller">[<?= $row['nr'] ?>]</span>
						<?php } ?><br />
					<?php } ?>
				</div>
			</div>


			<div class="row">
				<div class="col-md-12">
					<?php foreach($narrower as $row){ ?>
						<?php if($row['nr']>0){ ?>
							<strong>
						<?php } ?>
						⬆️ <a href="?concept=<?= $row['narrower'] ?>"><?= $row['label'] ?></a> 
						<?php if($row['nr']>0){ ?>
							</strong><span class="evensmaller">[<?= $row['nr'] ?>]</span>
						<?php } ?>
					<?php } ?>
				</div>

			</div>

		<?php } ?>

	</div>
</div>



<?php

include("_parts/footer.php");

?>
